<?php

namespace App\Services;

use App\Models\Colocation;

class BalanceService
{
    /**
    
     * @param Colocation $colocation
     * @return array<int, array{total_paid: float, share: float, balance: float}>
     */
    public function calculateBalances(Colocation $colocation): array
    {
        // Get active members with eager loading
        $activeMembers = $colocation->activeMembers()
            ->with([
                'expenses' => function ($query) use ($colocation) {
                    $query->where('colocation_id', $colocation->id);
                }
            ])
            ->get();

        $memberCount = $activeMembers->count();

        // Edge case: no active members
        if ($memberCount === 0) {
            return [];
        }

        // Calculate total expenses using DB sum for accuracy
        $totalExpenses = (float) $colocation->expenses()->sum('amount');

        // Calculate individual share
        $share = $memberCount > 0 ? round($totalExpenses / $memberCount, 2) : 0.0;

        // Build balance array for each active member
        $balances = [];

        foreach ($activeMembers as $member) {
            // Calculate total paid by this member for this colocation
            $totalPaid = (float) $member->expenses->sum('amount');

            // Calculate balance: what they paid - what they should have paid
            $balance = round($totalPaid - $share, 2);

            $balances[$member->id] = [
                'total_paid' => $totalPaid,
                'share' => $share,
                'balance' => $balance,
            ];
        }

        return $balances;
    }
}
