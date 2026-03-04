<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;


class InvitationPolicy
{
    /**
    
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
