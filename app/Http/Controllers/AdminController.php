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
    /**
     * Display the admin dashboard with global statistics.
     */
    public function index()
    {
        // Only allow global admins
        if (!auth()->user() || !auth()->user()->isGlobalAdmin()) {
            abort(403, 'Accès refusé. Réservé aux administrateurs.');
        }

        // Get statistics
        $totalUsers = User::count();
        $totalColocations = Colocation::count();
        $totalExpenses = Expense::count();
        $bannedUsers = User::where('is_banned', true)->count();

        // Get recent users
        $recentUsers = User::latest()->take(10)->get();

        // Get recent colocations
        $recentColocations = Colocation::with('users')
            ->latest()
            ->take(10)
            ->get();

        // Get active colocations
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

    /**
     * Ban a user.
     */
    public function ban(User $user): RedirectResponse
    {
        // Only allow global admins
        if (!auth()->user() || !auth()->user()->isGlobalAdmin()) {
            abort(403, 'Accès refusé. Réservé aux administrateurs.');
        }

        // Cannot ban yourself
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Vous ne pouvez pas vous bannir vous-même.');
        }

        // Ban the user
        $user->update(['is_banned' => true]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Utilisateur banni avec succès.');
    }

    /**
     * Unban a user.
     */
    public function unban(User $user): RedirectResponse
    {
        // Only allow global admins
        if (!auth()->user() || !auth()->user()->isGlobalAdmin()) {
            abort(403, 'Accès refusé. Réservé aux administrateurs.');
        }

        // Unban the user 
        $user->update(['is_banned' => false]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Utilisateur débanni avec succès.');
    }
}
