<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\User;

class ColocationPolicy
{
    /**
     * Determine whether the user can view the colocation.
     * Only active members (left_at = null) can view.
     */
    public function view(User $user, Colocation $colocation): bool
    {
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
           return false;
        }

        //  if ($user->is_admin) {
      //  return true;
    //}

        // Check if user is an active member (left_at is null)
        //return $membership->pivot->left_at === null;
        return $colocation->users()
        ->  where('user_id',$user->id)
        -> exists();
        
    }   

    /**
     * Determine whether the user can update the colocation.
     * Only the owner can update.
     */
    public function update(User $user, Colocation $colocation): bool
    {
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        return $membership->pivot->role === 'owner' && $membership->pivot->left_at === null;
    }

    /**
     * Determine whether the user can delete/cancel the colocation.
     * Only the owner can delete.
     */
    public function delete(User $user, Colocation $colocation): bool
    {
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        return $membership->pivot->role === 'owner' && $membership->pivot->left_at === null;
    }

    /**
     * Determine whether the user can invite members.
     * Only the owner can invite.
     */
    public function inviteMember(User $user, Colocation $colocation): bool
    {
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        return $membership->pivot->role === 'owner' && $membership->pivot->left_at === null;
    }

    /**
     * Determine whether the user can remove members.
     * Only the owner can remove (except themselves).
     */
    public function removeMember(User $user, Colocation $colocation, ?User $member = null): bool
    {
        // First check if the user is an owner of this colocation
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership || $membership->pivot->role !== 'owner' || $membership->pivot->left_at !== null) {
            return false;
        }

        // If no member specified, allow (owner can remove others)
        if ($member === null) {
            return true;
        }

        // Prevent owner from removing themselves
        if ($member->id === $user->id) {
            return false;
        }

        // Check if the member is actually in this colocation
        $memberMembership = $colocation->users()->where('user_id', $member->id)->first();

        return $memberMembership !== null;
    }
}
