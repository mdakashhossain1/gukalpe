<?php

namespace App\Modules\Cron\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP-triggered fallback for `schedule:run` on hosts (like this app's
 * current Hostinger shared plan) where a native `* * * * * php artisan
 * schedule:run` cron entry isn't available/set up. This account's existing
 * cron jobs are all curl-hits-a-token-URL, so this follows that same
 * convention instead of requiring SSH/native cron access - see MEMORY.md
 * 2026-08-09 for why the scheduler previously never ran at all here.
 */
class CronController extends Controller
{
    public function run(Request $request): Response
    {
        $secret = (string) config('cron.secret');
        $given = (string) $request->query('token');

        if ($secret === '' || $given === '' || ! hash_equals($secret, $given)) {
            abort(403);
        }

        Artisan::call('schedule:run');

        Log::info('Cron endpoint triggered schedule:run', ['output' => Artisan::output()]);

        return response('OK', 200);
    }
}
