<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Colocation;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    
    public function index()
    {
        // dyal global admin
        if (!auth()->user() || !auth()->user()->isGlobalAdmin()) {
            abort(403, 'Accès refusé. Réservé aux administrateurs.');
        }

        // njib statistics 
        $totalUsers = User::count();
        $totalColocations = Colocation::count();
        $totalExpenses = Expense::count();
        $bannedUsers = User::where('is_banned', true)->count();

        // njib users jdad
        $recentUsers = User::latest()->take(10)->get();

        // njib colocations jdad 
        $recentColocations = Colocation::with('users')
            ->latest()
            ->take(10)
            ->get();

        // njib colocations li active
        $activeColocations = Colocation::where('status', 'active')->count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalColocations',
            'totalExpenses',
            'bannedUsers',
            'recentUsers',
            'recentColocations',
            'activeColocations'
        ));
    }

   // ban dyal user 
    public function ban(User $user): RedirectResponse
    {
        // dyal global admins
        if (!auth()->user() || !auth()->user()->isGlobalAdmin()) {
            abort(403, 'Accès refusé. Réservé aux administrateurs.');
        }

        // mate9erch banner rasek
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Vous ne pouvez pas vous bannir vous-même.');
        }

        // Ban user
        $user->update(['is_banned' => true]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Utilisateur banni avec succès.');
    }

    
      // t7ayed ban 3la chi user
     
    public function unban(User $user): RedirectResponse
    {
        // dyal gha global admins
        if (!auth()->user() || !auth()->user()->isGlobalAdmin()) {
            abort(403, 'Accès refusé. Réservé aux administrateurs.');
        }

        // t7ayed ban 3la chi user 
        $user->update(['is_banned' => false]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Utilisateur débanni avec succès.');
    }
}
