<?php

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;

class SettlementPolicy
{
    
    public function confirm(User $user, Settlement $settlement): bool
    {
        $colocation = $settlement->colocation;

        if (!$colocation || $colocation->status !== 'active' || !$settlement->isPending()) {
            return false;
        }

        $isActiveMember = $colocation->activeMembers()->whereKey($user->id)->exists();

        if (!$isActiveMember) {
            return false;
        }

        return $user->id === (int) $settlement->receiver_id
            || $user->id === (int) $colocation->owner_id;
    }
}
