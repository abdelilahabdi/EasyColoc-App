<?php

namespace App\Services;

use App\Models\User;

class ColocationService
{
    /**
    
     * @param User $user
     * @return bool
     */
    public function userHasActiveColocation(User $user): bool
    {
        return $user->colocations()
            ->wherePivotNull('left_at')
            ->where('status', 'active')
            ->exists();
    }

    /**
     
     * @param User $user
     * @return array{can_join: bool, message: string|null}
     */
    public function canJoinColocation(User $user): array
    {
        if ($this->userHasActiveColocation($user)) {
            return [
                'can_join' => false,
                'message' => 'Vous faites déjà partie d\'une colocation active. Vous devez quitter votre colocation actuelle avant d\'en rejoindre une autre.'
            ];
        }

        return [
            'can_join' => true,
            'message' => null
        ];
    }
}
