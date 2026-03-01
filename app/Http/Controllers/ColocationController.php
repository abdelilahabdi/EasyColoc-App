<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Invitation;
use App\Models\transaction;
use App\Models\User;
use App\Services\ColocationService;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ColocationController extends Controller
{
    protected ColocationService $colocationService;
    protected InvitationService $invitationService;

    public function __construct(ColocationService $colocationService, InvitationService $invitationService)
    {
        $this->colocationService = $colocationService;
        $this->invitationService = $invitationService;
    }

    /**
     * Display a listing of user's colocations (active and archived).
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get all colocations (active, cancelled, left) with counts
        $colocations = $user->colocations()
            ->withCount(['users', 'expenses'])
            ->with('owner')
            ->orderBy('status', 'asc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($colocation) use ($user) {
                $membership = $colocation->users->where('id', $user->id)->first();
                $colocation->user_role = $membership->pivot->role ?? 'member';
                $colocation->user_left_at = $membership->pivot->left_at ?? null;
                return $colocation;
            });

        // Check if user can create a new colocation
        $canCreateNew = !$user->colocations()
            ->where('status', 'active')
            ->wherePivot('left_at', null)
            ->exists();

        return view('colocations.index', compact('colocations', 'canCreateNew'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('colocations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request
        $validated = $request->validate([
            'name' => ['required', 'string'],
        ]);

        $user = auth()->user();

        // Check if the user already has an active colocation
        $hasActiveColocation = $user->colocations()
            ->where('status', 'active')
            ->where(function ($query) use ($user) {
                $query->whereNull('colocation_user.left_at')
                    ->orWhere('colocation_user.left_at', '>', now());
            })
            ->exists();

        if ($hasActiveColocation) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous avez déjà une colocation active.');
        }

        // Create new colocation with owner_id
        $colocation = Colocation::create([
            'name' => $validated['name'],
            'status' => 'active',
            'owner_id' => $user->id,
        ]);

        // Attach the user as owner
        $user->colocations()->attach($colocation->id, ['role' => 'owner']);

        // Redirect to the show method
        return redirect()->route('colocations.show', $colocation);
    }

    /**
     * Display the specified resource.
     */
    public function show(Colocation $colocation)
    {
        $this->authorize('view', $colocation);

        // Retrieve colocation with its members, expenses, categories and settlements
        $colocation->load(['users', 'expenses.category', 'categories', 'settlements.sender', 'settlements.receiver']);

        // Calculate balances for each member
        $balances = $colocation->calculateBalances();

        // Get simplified debts (with settlements deducted)
        $simplifiedDebts = $colocation->getSimplifiedDebts();
        
        // Create transactions variable for backward compatibility
        $transactions = collect($simplifiedDebts)->map(function($debt) use ($colocation) {
            return (object) [
                'debtor' => $colocation->users->firstWhere('id', $debt['from']),
                'creditor' => $colocation->users->firstWhere('id', $debt['to']),
                'amount' => $debt['amount']
            ];
        });


        // Create a map of user_id => User for easy name lookup in the view
        $usersMap = $colocation->users->keyBy('id');

        // Get month filter from request
        $month = request('month');

        // Filter expenses by month if provided
        $expenses = $colocation->expenses()->with('category', 'user');
        if ($month) {
            $expenses = $expenses->whereYear('expense_date', substr($month, 0, 4))
                ->whereMonth('expense_date', substr($month, 5, 2));
        }
        $expenses = $expenses->orderBy('expense_date', 'desc')->get();

        return view('colocations.show', compact('colocation', 'balances', 'simplifiedDebts', 'transactions', 'usersMap', 'expenses', 'month'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Colocation $colocation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Colocation $colocation)
    {
        //
    }

    /**
     * Archive (cancel) the colocation instead of deleting.
     */
    public function destroy(Colocation $colocation): RedirectResponse
    {
        $this->authorize('delete', $colocation);

        if ($colocation->status === 'cancelled') {
            return redirect()->route('colocations.index')
                ->with('error', 'Cette colocation est déjà annulée.');
        }

        $colocation->update(['status' => 'cancelled']);

        return redirect()->route('colocations.index')
            ->with('success', 'Colocation archivée avec succès.');
    }

    /**
     * Allow a member to leave the colocation.
     */
    public function leave(Colocation $colocation): RedirectResponse
    {
        $user = auth()->user();

        // Check if user is a member of this colocation
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Vous n\'êtes pas membre de cette colocation.');
        }

        // Check if user is the owner (owner cannot leave, must cancel or transfer)
        if ($membership->pivot->role === 'owner') {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Vous êtes le propriétaire. Vous ne pouvez pas quitter la colocation. Vous pouvez l\'annuler.');
        }

        // Calculate balance to determine reputation impact
        $balances = $colocation->calculateBalances();
        $userBalance = $balances[$user->id] ?? 0;

        // If user owes money (negative balance), apply -1 reputation
        if ($userBalance < 0) {
            $user->decrement('reputation');
        } else {
            // If user has no debt or is owed money, apply +1 reputation
            $user->increment('reputation');
        }

        // Set left_at timestamp
        $colocation->users()->updateExistingPivot($user->id, ['left_at' => now()]);

        return redirect()->route('dashboard')
            ->with('success', 'Vous avez quitté la colocation.');
    }

    /**
     * Allow owner to remove a member from the colocation.
     */
    public function removeMember(Colocation $colocation, int $memberId): RedirectResponse
    {
        $member = User::findOrFail($memberId);

        $this->authorize('removeMember', [$colocation, $member]);

        // Check if member exists in colocation
        $memberInColocation = $colocation->users()->where('user_id', $memberId)->first();

        if (!$memberInColocation) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Ce membre n\'existe pas dans la colocation.');
        }

        // Check if trying to remove owner (should be handled by policy, but double check)
        if ($memberInColocation->pivot->role === 'owner') {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Vous ne pouvez pas retirer le propriétaire.');
        }

        // Calculate balance to determine reputation impact
        $balances = $colocation->calculateBalances();
        $memberBalance = $balances[$memberId] ?? 0;

        $user = auth()->user();

        // If member owes money, owner inherits the debt (adjustment)
        if ($memberBalance < 0) {
            // Member gets -1 reputation for leaving with debt
            $member->decrement('reputation');
            // Owner gets -1 reputation for not settling debts before removing member
            $user->decrement('reputation');
        } else {
            // Member gets +1 reputation for leaving without debt
            $member->increment('reputation');
        }

        // Set left_at timestamp
        $colocation->users()->updateExistingPivot($memberId, ['left_at' => now()]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Membre retiré de la colocation.');
    }

    /**
     * Allow owner to cancel the colocation.
     */
    public function cancel(Colocation $colocation): RedirectResponse
    {
        $this->authorize('delete', $colocation);

        $user = auth()->user();

        // Check if colocation is already cancelled
        if ($colocation->status === 'cancelled') {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Cette colocation est déjà annulée.');
        }

        // Calculate balances for all members
        $balances = $colocation->calculateBalances();

        // Apply reputation to owner based on debts
        $userBalance = $balances[$user->id] ?? 0;

        if ($userBalance < 0) {
            // Owner owes money - penalty
            $user->decrement('reputation');
        } else {
            // Owner is owed money or settled - bonus
            $user->increment('reputation');
        }

        // Mark colocation as cancelled
        $colocation->update(['status' => 'cancelled']);

        return redirect()->route('dashboard')
            ->with('success', 'Colocation annulée avec succès.');
    }
}
