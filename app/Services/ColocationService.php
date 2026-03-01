<?php

namespace App\Services;

use App\Models\User;

class ColocationService
{
    /**
     * Vérifie si l'utilisateur a une colocation active.
     * 
     * Critères :
     * - L'utilisateur a une relation dans colocation_user
     * - left_at est null (n'a pas quitté)
     * - La colocation associée a status = 'active'
     *
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
     * Vérifie si l'utilisateur peut rejoindre une colocation.
     * 
     * Retourne un tableau structuré :
     * - 'can_join' : bool - true si l'utilisateur peut rejoindre
     * - 'message' : string - message d'erreur si bloqué
     *
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
