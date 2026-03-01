<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can create categories in the colocation.
     * Only the owner can create categories.
     */
    public function create(User $user, $colocation): bool
    {
        // Vérifier que l'utilisateur est membre actif de la colocation
        $membership = $colocation->users()->where('user_id', $user->id)->first();
        
        if (!$membership || $membership->pivot->left_at !== null) {
            return false;
        }
        
        return $colocation->owner_id === $user->id;
    }

    /**
     * Determine whether the user can update the category.
     * Only the owner of the colocation can update.
     */
    public function update(User $user, Category $category): bool
    {
        $colocation = $category->colocation;
        
        if (!$colocation) {
            return false;
        }
        
        $membership = $colocation->users()->where('user_id', $user->id)->first();
        
        if (!$membership || $membership->pivot->left_at !== null) {
            return false;
        }
        
        return $colocation->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the category.
     * Only the owner of the colocation can delete.
     */
    public function delete(User $user, Category $category): bool
    {
        $colocation = $category->colocation;
        
        if (!$colocation) {
            return false;
        }
        
        $membership = $colocation->users()->where('user_id', $user->id)->first();
        
        if (!$membership || $membership->pivot->left_at !== null) {
            return false;
        }
        
        return $colocation->owner_id === $user->id;
    }
}
