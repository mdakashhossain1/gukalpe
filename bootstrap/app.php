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
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Drives Trust Builder / Growth Plan auto-maturity (Phase 1). No OS
        // cron exists in this repo/deployment yet - this closure only runs
        // when something actually invokes `php artisan schedule:run`, so a
        // real cron entry (`* * * * * php artisan schedule:run`) still needs
        // to be added wherever this app is deployed for maturity to be timely.
        $schedule->command('plans:mature-holdings')->everyMinute();

        // Daily "your investments grew today" digest email - once a day is
        // enough (the command itself is idempotent per-holding via
        // last_daily_return_email_sent_at, so a missed/retried run can't
        // double-send), timed for a morning send in IST.
        $schedule->command('plans:send-daily-returns-email')->dailyAt('09:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
