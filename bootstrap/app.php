<?php

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
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
        ]);

        // Kick banned users out on their next request, app-wide.
        // EnsureNotInMaintenance gates the public app when maintenance_mode is on
        // (admin panel stays open) — plan.md Section 41.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureNotInMaintenance::class,
            \App\Http\Middleware\EnsureUserNotBanned::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Matures holdings and credits ONLY profit to wallet.
        // Runs at 18:30 UTC = 00:00 IST (midnight India time) per spec Section 15/16.
        $schedule->command('plans:mature-holdings')->dailyAt('18:30');

        // Daily Profit Engine in-app notification (plan.md Section 15/24/25) —
        // 18:35 UTC = 00:05 IST, i.e. just after maturity runs, so holdings that
        // matured tonight are already withdrawn and get the maturity notification
        // instead of a "grew today" one. Reaches phone-only users the email misses.
        $schedule->command('plans:notify-daily-profit')->dailyAt('18:35');

        // Daily "your investments grew today" digest email — 09:00 IST = 03:30 UTC.
        $schedule->command('plans:send-daily-returns-email')->dailyAt('03:30');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
