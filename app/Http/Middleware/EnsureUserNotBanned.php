<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force-logs-out any authenticated user whose account has been banned since
 * they last loaded a page, so a mid-session ban takes effect on the very next
 * request (the login controllers separately refuse a banned user up front).
 * Runs on the whole web group.
 */
class EnsureUserNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has been suspended. Please contact support.');
        }

        return $next($request);
    }
}
