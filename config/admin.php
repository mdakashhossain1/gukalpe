<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Slug
    |--------------------------------------------------------------------------
    |
    | The URL path the admin panel is served at (e.g. "gullak-ops-4f2" ->
    | /gullak-ops-4f2). Deliberately not "admin" - an easily-guessed path is
    | half of what makes an unauthenticated admin panel dangerous. Change
    | this value any time; no code change or redeploy needed.
    |
    */

    'panel_slug' => env('ADMIN_PANEL_SLUG', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Password
    |--------------------------------------------------------------------------
    |
    | Gates the login form. Change periodically. See SECURITY.md for why this
    | is a plain compare (via hash_equals, not naive ==) rather than a hashed
    | password, and what to upgrade to before any real production use.
    |
    */

    'password' => env('ADMIN_PANEL_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Email
    |--------------------------------------------------------------------------
    |
    | Destination address for "new deposit request" notification emails (see
    | App\Mail\NewDepositRequestMail). Left empty until a real address is
    | supplied - sending is skipped entirely while empty, not an error. Real
    | delivery also requires MAIL_MAILER to be a real mailer, not "log".
    |
    */

    'notification_email' => env('ADMIN_NOTIFICATION_EMAIL'),

];
