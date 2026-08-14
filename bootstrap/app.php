<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureNotInMaintenance;
use App\Http\Middleware\EnsureUserNotBanned;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => AdminAuthenticate::class,
        ]);

        // Kick banned users out on their next request, app-wide.
        // EnsureNotInMaintenance gates the public app when maintenance_mode is on
        // (admin panel stays open) — plan.md Section 41.
        $middleware->web(append: [
            EnsureNotInMaintenance::class,
            EnsureUserNotBanned::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // All three times below are explicit IST (Asia/Kolkata) via
        // ->timezone() - previously these were written as pre-computed UTC
        // clock times (e.g. "18:30" with a comment explaining it's midnight
        // IST), which only worked as long as config('app.timezone') stayed
        // 'UTC' and nobody edited the number without redoing the math.
        // ->timezone() makes the intended wall-clock time unambiguous
        // regardless of the app's own timezone setting - the moment each job
        // actually fires is unchanged, this is a clarity/robustness fix, not
        // a schedule change. (Doesn't affect the now()->toDateString() -based
        // "already processed today" checks inside these commands themselves
        // - those stay tied to config('app.timezone') either way, and are
        // internally consistent since they compare against timestamps
        // written by that same UTC now(), not against the scheduler's
        // trigger time.)

        // Matures holdings and credits investment + profit to wallet.
        // Midnight IST, per spec Section 15/16.
        $schedule->command('plans:mature-holdings')->dailyAt('00:00')->timezone('Asia/Kolkata');

        // Daily Profit Engine in-app notification (plan.md Section 15/24/25) —
        // 5 minutes after midnight IST, i.e. just after maturity runs, so
        // holdings that matured tonight are already withdrawn and get the
        // maturity notification instead of a "grew today" one. Reaches
        // phone-only users the email misses.
        $schedule->command('plans:notify-daily-profit')->dailyAt('00:05')->timezone('Asia/Kolkata');

        // Daily "your investments grew today" digest email — 9 AM IST.
        $schedule->command('plans:send-daily-returns-email')->dailyAt('09:00')->timezone('Asia/Kolkata');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
