<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Determine whether the user can create expenses in the colocation.
     * Only active members (left_at = null) can create expenses.
     */
    public function create(User $user, Colocation $colocation): bool
    {
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership) {
            return false;
        }

        // Check if user is an active member (left_at is null)
        return $membership->pivot->left_at === null;
    }

    /**
     * Determine whether the user can delete the expense.
     * Owner of the colocation or creator of the expense can delete.
     */
    public function delete(User $user, Expense $expense): bool
    {
        // Get the colocation for this expense
        $colocation = $expense->colocation;

        if (!$colocation) {
            return false;
        }

        // Check if user is the owner of the colocation
        $membership = $colocation->users()->where('user_id', $user->id)->first();

        if (!$membership || $membership->pivot->left_at !== null) {
            return false;
        }

        // Owner can delete any expense
        if ($membership->pivot->role === 'owner') {
            return true;
        }

        // Creator can delete their own expense
        return $expense->payer_id === $user->id;
    }
}
