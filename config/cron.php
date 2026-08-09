<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cron Endpoint Secret
    |--------------------------------------------------------------------------
    |
    | Gates GET /cron/run?token=... (App\Modules\Cron\Controllers\CronController),
    | the curl-triggered fallback for `php artisan schedule:run` on hosts with
    | no native cron access. Compared via hash_equals - see SECURITY.md for
    | why this is a plain compare rather than a hashed value. Leave unset to
    | keep the endpoint permanently disabled (empty string never matches).
    |
    */

    'secret' => env('CRON_SECRET'),

];
