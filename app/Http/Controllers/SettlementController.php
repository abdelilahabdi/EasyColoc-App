<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementController extends Controller
{
    
     // Floating-point tolerance when comparing debt amounts
     
    private const AMOUNT_EPSILON = 0.01;

    
     // Store a newly created settlement and apply it immediately
     
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        abort_if(
            $colocation->status !== 'active',
            403,
            'Vous ne pouvez pas enregistrer de paiement pour une colocation archivee.'
        );

        $validated = $request->validate([
            'sender_id' => ['required', 'integer', 'exists:users,id'],
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'different:sender_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'settlement_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $senderId = (int) $validated['sender_id'];
        $receiverId = (int) $validated['receiver_id'];
        $amount = round((float) $validated['amount'], 2);

        if (!$this->isActiveMember($colocation, (int) $request->user()->id)) {
            abort(403, 'Vous n\'etes pas un membre actif de cette colocation.');
        }

        if (!$this->isActiveMember($colocation, $senderId)) {
            abort(403, 'Le payeur n\'est pas un membre actif de cette colocation.');
        }

        if (!$this->isActiveMember($colocation, $receiverId)) {
            abort(403, 'Le beneficiaire n\'est pas un membre actif de cette colocation.');
        }

        $outstandingDebt = $this->findOutstandingDebtAmount($colocation, $senderId, $receiverId);

        if ($outstandingDebt === null) {
            throw ValidationException::withMessages([
                'amount' => 'Ce reglement ne correspond a aucune dette en cours.',
            ]);
        }

        if ($amount - $outstandingDebt > self::AMOUNT_EPSILON) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Le montant du reglement ne peut pas depasser la dette restante de %.2f EUR.',
                    $outstandingDebt
                ),
            ]);
        }

        $duplicateSettlementExists = $colocation->settlements()
            ->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('amount', $amount)
            ->whereDate('settlement_date', $validated['settlement_date'])
            ->exists();

        if ($duplicateSettlementExists) {
            throw ValidationException::withMessages([
                'amount' => 'Ce paiement a deja ete enregistre.',
            ]);
        }

        DB::transaction(function () use ($colocation, $senderId, $receiverId, $amount, $validated): void {
            $colocation->settlements()->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'settlement_date' => $validated['settlement_date'],
                'status' => Settlement::STATUS_COMPLETED,
            ]);
        });

        return redirect()->back()
            ->with('success', 'Paiement marque comme paye avec succes.');
    }

    
      // Confirm a pending settlement
     
    public function confirm(Settlement $settlement): RedirectResponse
    {
        $settlement->loadMissing('colocation');

        $this->authorize('confirm', $settlement);

        if ($settlement->isCompleted()) {
            return redirect()->back()
                ->with('error', 'Ce paiement a deja ete confirme.');
        }

        $outstandingDebt = $this->findOutstandingDebtAmount(
            $settlement->colocation,
            (int) $settlement->sender_id,
            (int) $settlement->receiver_id
        );

        if ($outstandingDebt === null || ((float) $settlement->amount - $outstandingDebt) > self::AMOUNT_EPSILON) {
            return redirect()->route('colocations.show', $settlement->colocation)
                ->with('error', 'Ce paiement ne correspond plus a la dette restante. Veuillez actualiser la page.');
        }

        DB::transaction(function () use ($settlement): void {
            $settlement->update(['status' => Settlement::STATUS_COMPLETED]);
        });

        return redirect()->route('colocations.show', $settlement->colocation)
            ->with('success', 'Paiement confirme avec succes.');
    }

    
     // Determine whether the given user still belongs to the active member list
     
    private function isActiveMember(Colocation $colocation, int $userId): bool
    {
        return $colocation->activeMembers()->whereKey($userId)->exists();
    }

    
     // Resolve the current outstanding debt for a debtor/creditor pair.
     
    private function findOutstandingDebtAmount(Colocation $colocation, int $senderId, int $receiverId): ?float
    {
        foreach ($colocation->getSimplifiedDebts() as $debt) {
            if ((int) $debt['from'] !== $senderId || (int) $debt['to'] !== $receiverId) {
                continue;
            }

            return round((float) $debt['amount'], 2);
        }

        return null;
    }
}
