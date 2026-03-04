<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\User;
use App\Services\ColocationService;
use App\Services\InvitationService;
use App\Services\ReputationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColocationController extends Controller
{
    protected ColocationService $colocationService;
    protected InvitationService $invitationService;
    protected ReputationService $reputationService;

    public function __construct(
        ColocationService $colocationService,
        InvitationService $invitationService,
        ReputationService $reputationService
    ) {
        $this->colocationService = $colocationService;
        $this->invitationService = $invitationService;
        $this->reputationService = $reputationService;
    }


     // wri l user colocation kamlin active
     
    public function index()
    {
        $user = auth()->user();

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

        $canCreateNew = !$user->colocations()
            ->where('status', 'active')
            ->wherePivot('left_at', null)
            ->exists();

        return view('colocations.index', compact('colocations', 'canCreateNew'));
    }


     // Show form for creating new resource
    
    public function create()
    {
        return view('colocations.create');
    }

    
     // khezen wa7ed element jdid f db
     
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
        ]);

        $user = auth()->user();

        $hasActiveColocation = $user->colocations()
            ->where('status', 'active')
            ->where(function ($query) use ($user) {
                $query->whereNull('colocation_user.left_at')
                    ->orWhere('colocation_user.left_at', '>', now());
            })
            ->exists();

        if ($hasActiveColocation) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous avez deja une colocation active.');
        }

        $colocation = Colocation::create([
            'name' => $validated['name'],
            'status' => 'active',
            'owner_id' => $user->id,
        ]);

        $user->colocations()->attach($colocation->id, ['role' => 'owner']);

        return redirect()->route('colocations.show', $colocation);
    }

    
      // afficher element li te7eded w specifier 
     
    public function show(Colocation $colocation)
    {
        $this->authorize('view', $colocation);

        $colocation->load(['users', 'expenses.category', 'categories', 'settlements.sender', 'settlements.receiver']);

        $balances = $colocation->calculateBalancesWithSettlements();
        $simplifiedDebts = $colocation->getSimplifiedDebts();

        $transactions = collect($simplifiedDebts)->map(function ($debt) use ($colocation) {
            return (object) [
                'debtor' => $colocation->users->firstWhere('id', $debt['from']),
                'creditor' => $colocation->users->firstWhere('id', $debt['to']),
                'amount' => $debt['amount'],
            ];
        });

        $usersMap = $colocation->users->keyBy('id');
        $month = request('month');

        $expenses = $colocation->expenses()->with('category', 'user');

        if ($month) {
            $expenses = $expenses->whereYear('expense_date', substr($month, 0, 4))
                ->whereMonth('expense_date', substr($month, 5, 2));
        }

        $expenses = $expenses->orderBy('expense_date', 'desc')->get();

        return view('colocations.show', compact('colocation', 'balances', 'simplifiedDebts', 'transactions', 'usersMap', 'expenses', 'month'));
    }

    
      // afficher form pour modifier 3la had element 
     
    public function edit(Colocation $colocation)
    {
        //
    }

    
     // pdate donner dyal had ellement f db 
     
    public function update(Request $request, Colocation $colocation)
    {
        //
    }

    
     // khliha archiver blaset ma delete
     
    public function destroy(Colocation $colocation): RedirectResponse
    {
        $this->authorize('delete', $colocation);

        if ($colocation->status === 'cancelled') {
            return redirect()->route('colocations.index')
                ->with('error', 'Cette colocation est deja annulee.');
        }

        $colocation->update(['status' => 'cancelled']);

        return redirect()->route('colocations.index')
            ->with('success', 'Colocation archivee avec succes.');
    }

    
     // khli member yekherej men colocation
     
    public function leave(Colocation $colocation): RedirectResponse
    {
        $user = auth()->user();
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Vous n\'etes pas membre de cette colocation.');
        }

        if ($membership->pivot->role === 'owner') {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Vous etes le proprietaire. Vous ne pouvez pas quitter la colocation. Vous pouvez l\'annuler.');
        }

        $reputationDelta = $this->reputationService->adjustReputationOnDeparture($user, $colocation);

        $colocation->users()->updateExistingPivot($user->id, ['left_at' => now()]);

        return redirect()->route('dashboard')
            ->with(
                'success',
                sprintf(
                    'Vous avez quitte la colocation. Reputation %s%d.',
                    $reputationDelta > 0 ? '+' : '',
                    $reputationDelta
                )
            );
    }

    
     // kheli owner remove a member from colocation
     
    public function removeMember(Colocation $colocation, int $memberId): RedirectResponse
    {
        $member = User::findOrFail($memberId);

        $this->authorize('removeMember', [$colocation, $member]);

        $memberInColocation = $colocation->users()->where('user_id', $memberId)->first();

        if (!$memberInColocation) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Ce membre n\'existe pas dans la colocation.');
        }

        if ($memberInColocation->pivot->role === 'owner') {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Vous ne pouvez pas retirer le proprietaire.');
        }

        $owner = auth()->user();
        $reputationChanges = $this->reputationService->adjustReputationOnMemberRemoval($owner, $member, $colocation);

        $colocation->users()->updateExistingPivot($memberId, ['left_at' => now()]);

        return redirect()->route('colocations.show', $colocation)
            ->with(
                'success',
                $reputationChanges['had_debt']
                    ? 'Membre retire avec dette. Reputation membre -1, owner -1.'
                    : 'Membre retire sans dette. Reputation membre +1.'
            );
    }

    
      // kheli owner cancel colocation.
     
    public function cancel(Colocation $colocation): RedirectResponse
    {
        $this->authorize('delete', $colocation);

        $owner = auth()->user();

        if ($colocation->status === 'cancelled') {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Cette colocation est deja annulee.');
        }

        $reputationDelta = $this->reputationService->adjustReputationOnCancellation($owner, $colocation);

        $colocation->update(['status' => 'cancelled']);

        return redirect()->route('dashboard')
            ->with(
                'success',
                sprintf(
                    'Colocation annulee avec succes. Reputation %s%d.',
                    $reputationDelta > 0 ? '+' : '',
                    $reputationDelta
                )
            );
    }
}
