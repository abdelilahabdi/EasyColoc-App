<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class SettlementController extends Controller
{
    /**
     * Store a newly created settlement in storage.
     * 
     * Validates the payment and ensures:
     * - The user is a member of the colocation
     * - Only the sender, receiver, or Owner can create a settlement
     */
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        // Check if colocation is active
        if ($colocation->status !== 'active') {
            abort(403, 'Vous ne pouvez pas enregistrer de paiement pour une colocation archivée.');
        }

        // Validate the request
        $validated = $request->validate([
            'sender_id' => ['required', 'exists:users,id'],
            'receiver_id' => ['required', 'exists:users,id', 'different:sender_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'settlement_date' => ['required', 'date'],
        ]);

        // Check if the authenticated user is a member of the colocation
        if (!$colocation->users()->where('users.id', auth()->id())->exists()) {
            abort(403, 'Vous n\'êtes pas membre de cette colocation.');
        }

        // Get the sender and receiver to check roles
        $sender = \App\Models\User::find($validated['sender_id']);
        $receiver = \App\Models\User::find($validated['receiver_id']);

        // Check if sender and receiver are members of the colocation
        if (!$colocation->users()->where('users.id', $validated['sender_id'])->exists()) {
            abort(403, 'Le payeur n\'est pas membre de cette colocation.');
        }

        if (!$colocation->users()->where('users.id', $validated['receiver_id'])->exists()) {
            abort(403, 'Le bénéficiaire n\'est pas membre de cette colocation.');
        }

        // Check authorization: only sender, receiver, or Owner can create settlement
        $isSender = auth()->id() === $validated['sender_id'];
        $isReceiver = auth()->id() === $validated['receiver_id'];
        $isOwner = Gate::allows('update', $colocation);

        if (!$isSender && !$isReceiver && !$isOwner) {
            abort(403, 'Vous n\'êtes pas autorisé à valider ce paiement.');
        }

        // Create the settlement
        $settlement = Settlement::create([
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'],
            'colocation_id' => $colocation->id,
            'amount' => $validated['amount'],
            'settlement_date' => $validated['settlement_date'],
            'status' => 'completed',
        ]);

        // Add +10 reputation points to the sender (the one who paid)
        $sender->increment('reputation', 10);

        return redirect()->back()
            ->with('success', 'Paiement marqué comme effectué avec succès. +10 points de réputation!');
    }
}
