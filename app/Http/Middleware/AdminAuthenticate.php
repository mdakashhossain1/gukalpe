<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    /**
     * Name of the long-lived "remember me" cookie. Set once at login, it lets
     * the admin stay signed in for 30 days even after the (short-lived) PHP
     * session expires - each request re-establishes the session flag from it.
     */
    public const REMEMBER_COOKIE = 'admin_remember';

    /** 30 days, in minutes - Laravel cookie lifetimes are expressed in minutes. */
    public const REMEMBER_MINUTES = 43200;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('admin_authenticated')) {
            return $next($request);
        }

        // No live session, but a valid remember cookie keeps them logged in.
        $remember = (string) $request->cookie(self::REMEMBER_COOKIE, '');
        if ($remember !== '' && hash_equals(self::rememberToken(), $remember)) {
            $request->session()->put('admin_authenticated', true);

            // Slide the window forward so an active admin stays logged in.
            Cookie::queue(Cookie::make(self::REMEMBER_COOKIE, $remember, self::REMEMBER_MINUTES));

            return $next($request);
        }

        return redirect()->route('admin.login');
    }

    /**
     * Deterministic token bound to the current admin password, so rotating the
     * password automatically invalidates every outstanding remember cookie.
     * The cookie itself is also encrypted by Laravel's EncryptCookies layer.
     */
    public static function rememberToken(): string
    {
        return hash_hmac('sha256', 'admin-remember-v1', (string) config('admin.password').config('app.key'));
    }
}
