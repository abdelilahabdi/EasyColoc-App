<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BannedUser
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user is banned and logs them out if so.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isBanned()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Votre compte a été banni. Veuillez contacter l\'administrateur.');
        }

        return $next($request);
    }
}
