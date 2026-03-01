<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

/**
 * Policy pour les actions liées aux invitations
 * 
 * Règles d'autorisation:
 * - view: L'email de l'invitation doit correspondre à l'utilisateur
 * - accept: L'email de l'invitation doit correspondre à l'utilisateur et l'invitation doit être pending
 * - decline: L'email de l'invitation doit correspondre à l'utilisateur et l'invitation doit être pending
 * - destroy: Seul l'owner de la colocation peut révoquer l'invitation
 */
class InvitationPolicy
{
    /**
     * Détermine si l'utilisateur peut voir l'invitation
     * 
     * Conditions:
     * - L'email de l'invitation correspond à l'utilisateur
     * - OU l'utilisateur est l'owner de la colocation
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return bool
     */
    public function view(User $user, Invitation $invitation): bool
    {
        // Email must match
        if (strtolower($user->email) === strtolower($invitation->email)) {
            return true;
        }

        // Or user is the owner of the colocation
        $colocation = $invitation->colocation;
        if ($colocation && $colocation->owner_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Détermine si l'utilisateur peut accepter l'invitation
     * 
     * Conditions:
     * - L'email de l'invitation correspond à l'utilisateur
     * - L'invitation est en status 'pending'
     * - L'invitation n'a pas expiré
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return bool
     */
    public function accept(User $user, Invitation $invitation): bool
    {
        // Email must match
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return false;
        }

        // Invitation must be pending
        if ($invitation->status !== 'pending') {
            return false;
        }

        // Invitation must not be expired
        if ($invitation->isExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Détermine si l'utilisateur peut refuser l'invitation
     * 
     * Conditions:
     * - L'email de l'invitation correspond à l'utilisateur
     * - L'invitation est en status 'pending'
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return bool
     */
    public function decline(User $user, Invitation $invitation): bool
    {
        // Email must match
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return false;
        }

        // Invitation must be pending
        if ($invitation->status !== 'pending') {
            return false;
        }

        return true;
    }

    /**
     * Détermine si l'utilisateur peut révoquer/supprimer l'invitation
     * 
     * Conditions:
     * - L'utilisateur est l'owner de la colocation
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return bool
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        $colocation = $invitation->colocation;

        if (!$colocation) {
            return false;
        }

        // Only the owner can revoke invitations
        return $colocation->owner_id === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut envoyer une invitation
     * 
     * Utilise la méthode inviteMember de ColocationPolicy
     * 
     * @param User $user
     * @param Invitation $invitation
     * @return bool
     */
    public function create(User $user, Invitation $invitation): bool
    {
        // This is handled by ColocationPolicy::inviteMember
        // This method exists for consistency
        $colocation = $invitation->colocation ?? null;

        if (!$colocation) {
            return false;
        }

        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        return $membership->pivot->role === 'owner' && $membership->pivot->left_at === null;
    }
}
