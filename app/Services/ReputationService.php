<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\User;


class ReputationService
{
    
    private const DEBT_EPSILON = 0.01;

    
    public function adjustReputationOnDeparture(User $user, Colocation $colocation): int
    {
        if ($this->hasDebt($colocation, $user->id)) {
            return $this->decrementReputation($user);
        }

        return $this->incrementReputation($user);
    }

    /**
     * Ajuste la reputation lors du retrait d'un membre par le owner.
     *
     * @return array{member: int, owner: int, had_debt: bool}
     */
    public function adjustReputationOnMemberRemoval(
        User $owner,
        User $removedMember,
        Colocation $colocation
    ): array {
        $hadDebt = $this->hasDebt($colocation, $removedMember->id);

        if ($hadDebt) {
            return [
                'member' => $this->decrementReputation($removedMember),
                'owner' => $this->decrementReputation($owner),
                'had_debt' => true,
            ];
        }

        return [
            'member' => $this->incrementReputation($removedMember),
            'owner' => 0,
            'had_debt' => false,
        ];
    }

    
    public function adjustReputationOnCancellation(User $owner, Colocation $colocation): int
    {
        if ($this->hasDebt($colocation, $owner->id)) {
            return $this->decrementReputation($owner);
        }

        return $this->incrementReputation($owner);
    }

    
    public function hasDebt(Colocation $colocation, int $userId): bool
    {
        return $this->getBalance($colocation, $userId) < -self::DEBT_EPSILON;
    }

    
    public function getBalance(Colocation $colocation, int $userId): float
    {
        $balances = $colocation->calculateBalancesWithSettlements();

        return round((float) ($balances[$userId] ?? 0.0), 2);
    }

    
    protected function incrementReputation(User $user): int
    {
        $user->increment('reputation');

        return 1;
    }

   
    protected function decrementReputation(User $user): int
    {
        $user->decrement('reputation');

        return -1;
    }

    
    public function getReputationLevel(User $user): string
    {
        $reputation = $user->reputation;

        if ($reputation >= 10) {
            return 'Excellent';
        }

        if ($reputation >= 5) {
            return 'Bon';
        }

        if ($reputation >= 1) {
            return 'Moyen';
        }

        return 'Faible';
    }
}
