<?php

namespace App\Services;

use App\Models\User;
use App\Models\Colocation;

/**
 * Service de gestion de la réputation des utilisateurs
 * 
 * Ce service gère:
 * - L'ajustement de réputation lors d'un départ ou annulation
 * - L'imputation de dette au owner quand un membre est retiré
 * 
 * Règles de réputation:
 * - Départ/annulation avec dette: -1 point
 * - Départ/annulation sans dette: +1 point
 * - Owner qui retire un membre avec dette: dette imputée à l'owner (-1 pour l'owner)
 */
class ReputationService
{
    /**
     * BalanceService injecté pour calculer les balances
     */
    protected BalanceService $balanceService;

    /**
     * Constructeur avec injection de dépendance
     */
    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Ajuste la réputation lors du départ d'un membre
     * 
     * Règles:
     * - Si le membre a une dette (balance < 0): -1 réputation
     * - Si le membre n'a pas de dette (balance >= 0): +1 réputation
     * 
     * @param User $user Utilisateur qui quitte
     * @param Colocation $colocation Colocation concernée
     * @return int Le changement de réputation appliqué (-1, 0, ou +1)
     */
    public function adjustReputationOnDeparture(User $user, Colocation $colocation): int
    {
        // Calculer la balance de l'utilisateur dans cette colocation
        $balances = $this->balanceService->calculateBalances($colocation);

        // Vérifier si l'utilisateur a une balance enregistrée
        if (!isset($balances[$user->id])) {
            // Utilisateur non trouvé dans les balances - pas de changement
            return 0;
        }

        $balance = $balances[$user->id]['balance'];

        // Déterminer le changement de réputation
        if ($balance < 0) {
            // Dette existante: -1 réputation
            $this->decrementReputation($user);
            return -1;
        } else {
            // Pas de dette: +1 réputation
            $this->incrementReputation($user);
            return 1;
        }
    }

    /**
     * Impute la dette d'un membre retiré au owner
     * 
     * Quand un owner retire un membre avec une dette:
     * - La dette du membre est transférée à l'owner
     * - L'owner perd 1 point de réputation
     * 
     * @param Colocation $colocation Colocation concernée
     * @param User $removedMember Membre qui est retiré
     * @return bool True si la dette a été imputée, false sinon
     */
    public function imputeDebtToOwner(Colocation $colocation, User $removedMember): bool
    {
        // Vérifier que la colocation a un owner
        if (!$colocation->owner_id) {
            return false;
        }

        // Calculer les balances
        $balances = $this->balanceService->calculateBalances($colocation);

        // Vérifier si le membre retiré avait une dette
        if (!isset($balances[$removedMember->id])) {
            return false;
        }

        $memberBalance = $balances[$removedMember->id]['balance'];

        // Si le membre doit de l'argent (balance négative)
        if ($memberBalance < 0) {
            // Imputer la dette au owner
            $owner = User::find($colocation->owner_id);

            if ($owner) {
                // L'owner perd 1 point de réputation pour avoir retiré un membre avec dette
                $this->decrementReputation($owner);
                return true;
            }
        }

        return false;
    }

    /**
     * Ajuste la réputation lors d'une annulation de colocation
     * 
     * Si l'owner annule la colocation:
     * - Avec dette envers les membres: -1 réputation
     * - Sans dette: pas de changement
     * 
     * @param User $owner Propriétaire qui annule
     * @param Colocation $colocation Colocation annulée
     * @return int Le changement de réputation appliqué
     */
    public function adjustReputationOnCancellation(User $owner, Colocation $colocation): int
    {
        $balances = $this->balanceService->calculateBalances($colocation);

        // Vérifier si l'owner a une balance positive (il doit de l'argent aux autres)
        if (isset($balances[$owner->id]) && $balances[$owner->id]['balance'] > 0) {
            $this->decrementReputation($owner);
            return -1;
        }

        return 0;
    }

    /**
     * Incrémente la réputation d'un utilisateur
     * 
     * @param User $user
     * @return void
     */
    protected function incrementReputation(User $user): void
    {
        $user->increment('reputation');
    }

    /**
     * Décrémente la réputation d'un utilisateur
     * 
     * @param User $user
     * @return void
     */
    protected function decrementReputation(User $user): void
    {
        // Empêcher la réputation de devenir négative
        $newReputation = max(0, $user->reputation - 1);
        $user->update(['reputation' => $newReputation]);
    }

    /**
     * Récupère le niveau de réputation d'un utilisateur
     * 
     * @param User $user
     * @return string Niveau de réputation (Excellent, Bon, Moyen, Faible)
     */
    public function getReputationLevel(User $user): string
    {
        $reputation = $user->reputation;

        if ($reputation >= 10) {
            return 'Excellent';
        } elseif ($reputation >= 5) {
            return 'Bon';
        } elseif ($reputation >= 1) {
            return 'Moyen';
        } else {
            return 'Faible';
        }
    }
}
