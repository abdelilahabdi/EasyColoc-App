<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\Payment;
use App\Models\User;

/**
 * Policy pour les actions liées aux paiements
 * 
 * Règles d'autorisation:
 * - Seul un membre actif ou un admin peut marquer un paiement comme payé
 * - Le paiement doit appartenir à la même colocation
 */
class PaymentPolicy
{
    /**
     * Détermine si l'utilisateur peut marquer un paiement comme payé
     * 
     * Conditions:
     * - L'utilisateur est un admin global
     * - OU l'utilisateur est un membre actif de la colocation (left_at = null)
     * 
     * @param User $user Utilisateur authentifié
     * @param Payment $payment Paiement à marquer comme payé
     * @return bool
     */
    public function markAsPaid(User $user, Payment $payment): bool
    {
        // Admin global peut effectuer cette action
        if ($user->isAdmin()) {
            return true;
        }

        // Récupérer la colocation du paiement
        $colocation = $payment->colocation;

        if (!$colocation) {
            return false;
        }

        // Vérifier si l'utilisateur est un membre actif de la colocation
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        // Vérifier si l'utilisateur est un membre actif (left_at est null)
        return $membership->pivot->left_at === null;
    }

    /**
     * Détermine si l'utilisateur peut voir les détails d'un paiement
     * 
     * @param User $user
     * @param Payment $payment
     * @return bool
     */
    public function view(User $user, Payment $payment): bool
    {
        // Admin global peut voir tous les paiements
        if ($user->isAdmin()) {
            return true;
        }

        $colocation = $payment->colocation;

        if (!$colocation) {
            return false;
        }

        // Vérifier si l'utilisateur est membre de la colocation
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        // Vérifier si l'utilisateur est un membre actif
        return $membership->pivot->left_at === null;
    }

    /**
     * Détermine si l'utilisateur peut créer un paiement
     * 
     * @param User $user
     * @param Colocation $colocation
     * @return bool
     */
    public function create(User $user, Colocation $colocation): bool
    {
        // Admin global peut créer des paiements
        if ($user->isAdmin()) {
            return true;
        }

        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        // Seul un membre actif peut créer un paiement
        return $membership->pivot->left_at === null;
    }
}
