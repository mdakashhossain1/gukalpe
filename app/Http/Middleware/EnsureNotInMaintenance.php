<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DB-driven maintenance mode (plan.md Section 41). When the admin flips
 * maintenance_mode on, every public request gets a 503 maintenance page - but
 * the admin panel stays reachable so an admin can always switch it back off.
 * Runs on the whole web group.
 */
class EnsureNotInMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AppSetting::enabled('maintenance_mode')) {
            $adminSlug = config('admin.panel_slug', 'admin');

            if (! $request->is($adminSlug) && ! $request->is($adminSlug.'/*') && ! $request->is('cron/*')) {
                return response()->view('maintenance', [], 503);
            }
        }

        return $next($request);
    }
}
