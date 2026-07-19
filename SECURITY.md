# SECURITY.md

## Known incident: `.env` was web-accessible (fixed 2026-07-15)

Adding `index.php` directly in this directory (so XAMPP can serve the app from
`htdocs/gullakpe/gullakpe-laravel/` instead of requiring the vhost to point at
`public/`) has a serious side effect: **this entire directory becomes part of
the web root**, not just `public/`. Verified directly:

```
GET /gullakpe/gullakpe-laravel/.env                    → was 200 (plaintext DB creds, APP_KEY)
GET /gullakpe/gullakpe-laravel/composer.json           → was 200
GET /gullakpe/gullakpe-laravel/vendor/autoload.php     → was 200
GET /gullakpe/gullakpe-laravel/storage/logs/laravel.log → was 200
```

Fixed by adding deny rules to the root `.htaccess` (before the general rewrite
rules) that `[F,L]`-block dotfiles, and the `app/ bootstrap/ config/ database/
resources/ routes/ storage/ tests/ vendor/ node_modules/` directories, plus
`composer.json/.lock`, `package.json/-lock.json`, `phpunit.xml`, `artisan`,
`vite.config.js`, `README.md`, `CHANGELOG.md`. Re-verified after the fix — all
of the above now return 403, and `GET /gullakpe/gullakpe-laravel/` (the app
itself) still returns 200.

**If you ever edit or regenerate this folder's `.htaccess`, re-run this check
before calling the change done:**

```powershell
foreach ($p in @('.env','composer.json','vendor/autoload.php','storage/logs/laravel.log')) {
  try { $r = Invoke-WebRequest "http://localhost/gullakpe/gullakpe-laravel/$p" -UseBasicParsing -TimeoutSec 5; "OPEN: $p -> $($r.StatusCode)" }
  catch { "blocked: $p -> $($_.Exception.Response.StatusCode.value__)" }
}
```

Expect every path above to be blocked (403), and the app root to still return 200.

## General checklist for this project

- Never commit `.env`. Keep `APP_DEBUG=false` and a real, unique `APP_KEY` in
  any non-local environment (local dev currently uses `APP_DEBUG=true`, which
  is fine only because it's local-only).
- Modules registered via `App\Providers\ModuleServiceProvider` are auto-wired
  onto the `web` middleware group with no auth/authorization applied by
  default — when adding real modules under `app/Modules/`, apply
  `auth`/authorization middleware explicitly per route group; don't assume the
  loader adds any protection.
- The planned auth flow (phone + OTP + MPIN, see DESIGN.md) touches several
  classic risk areas once implemented server-side:
  - Rate-limit OTP send/verify and MPIN login attempts (the prototype already
    has a client-side "max attempts" lockout modal — back it with real
    server-side throttling, don't rely on the client).
  - Never store MPIN or OTP in plaintext; hash MPIN like a password.
  - Phone number is a de facto username — validate/normalize format server-side
    regardless of the client input mask.
- Use HTTPS and secure/`SESSION_ENCRYPT=true` cookies once this leaves local dev.
- Keep dependencies current (`composer outdated`, `npm outdated`) — this is a
  fresh Laravel 12 skeleton today, but that will drift.

## Static assets (build/, lang/) are served by index.php itself, not .htaccess

The `.htaccess` rule that tried to pass `build/`, `lang/`, etc. straight to
`public/` via `%{DOCUMENT_ROOT}/public%{REQUEST_URI}` never matched — it
assumed this directory *is* the vhost document root, but under XAMPP it's
nested two levels under `htdocs`, so `DOCUMENT_ROOT` pointed too high and
every asset request 404'd. Fixed by moving that logic into `index.php`: it
resolves the request path relative to itself (via `SCRIPT_NAME`, so it works
at any mount depth), and if a real file exists under `public/` for that path,
streams it directly with an extension-based Content-Type (`mime_content_type()`
alone misidentifies `.js`/`.css` as `text/plain`, which browsers reject for
`<script type="module">`). Only falls through to `require public/index.php`
(the real Laravel app) if no such static file exists.

**Re-run this check after touching `index.php` or `.htaccess`:**

```powershell
foreach ($p in @('lang/en.json','build/assets/app-06LUoARF.js')) {  # update filenames after each `npm run build`
  $r = Invoke-WebRequest "http://localhost/gullakpe/gullakpe-laravel/$p" -UseBasicParsing
  "$p -> $($r.StatusCode) ($($r.Headers['Content-Type']))"
}
```

Expect 200 with `application/json` / `application/javascript` / `text/css` —
not `text/plain`, and not 404.

## Ops console (admin panel) — added 2026-07-15

Replaces the removed unauthenticated debug modal (see MEMORY.md's
2026-07-15 (9) entry) with a real, gated admin route. Full feature
documentation is in DESIGN.md; this section is the security-specific detail.

- **Two independent layers, not one**: an obscure URL (`ADMIN_PANEL_SLUG`)
  *and* a password gate (`ADMIN_PANEL_PASSWORD`), both in `.env`. Neither
  alone would be adequate — an obscure URL with no login is trivially
  bypassed by anyone who finds it (browser history, a shared link, a scan);
  a login gate at a guessable `/admin` invites brute-force attempts against
  a known target. Change both values periodically; changing the slug is a
  one-line `.env` edit, no code change.
- **Password comparison is `hash_equals()` against a plaintext `.env` value,
  not a bcrypt hash.** This is a deliberate, documented tradeoff for a
  single-admin local tool, not an oversight: `.env` is already access-controlled
  (blocked from direct HTTP access — see the incident above), and
  `hash_equals()` prevents timing-attack leakage of the comparison itself.
  **Before any real production deployment**, upgrade this to a hashed
  password (`Hash::make()`/`Hash::check()`) at minimum, and consider real
  multi-admin auth (Laravel's `auth` scaffolding, a `users` table with an
  `is_admin` flag) if more than one person needs access — the current design
  assumes a single operator who controls the `.env` file directly.
- **Progressive lockout (strengthened 2026-07-15), not a flat rate limit**:
  the original version allowed 5 attempts per IP per 60 seconds via Laravel's
  `RateLimiter` — but that only costs an attacker a 1-minute wait, forever
  (5/minute is still ~7,200 guesses/day against one password). Replaced with
  a persistent failure counter per IP (`Cache`, 24h TTL) that escalates the
  lockout as failures accumulate: 5 fails → 1 minute, 10 → 15 minutes,
  15 → 1 hour, 20 → 24 hours. The counter survives across lockout windows
  (unlike a plain rate limiter, which forgets once its window expires), so
  waiting out one lockout and resuming doesn't reset the attacker's cost back
  to tier 1. Verified end-to-end via HTTP: 5 consecutive wrong passwords
  correctly triggered the 1-minute lockout on the 5th attempt.
- **Every attempt is logged** to a dedicated channel (`admin_security` in
  `config/logging.php`, writing to `storage/logs/admin-security-{date}.log`,
  90-day retention) — IP, failure count, lockout duration, user agent for
  failures; IP only for successes and logouts. Kept separate from the general
  Laravel log specifically so reviewing admin access doesn't mean sifting
  through unrelated app noise. **Check this log periodically** — sustained
  failed attempts from one IP (or a burst from many different ones) is the
  signal that the slug has leaked and both it and the password should be
  rotated immediately.
- Still IP-scoped, which has a known limit: distributed attempts from many
  IPs (botnet, rotating proxy/VPN) each get their own fresh tier-1 budget.
  Proportionate for a single-operator local tool; a real production
  deployment should add a global attempt cap on top (e.g. total failures
  across all IPs in a short window) and/or a CAPTCHA-style challenge after
  the first lockout tier.
- **Session-based, not stateless**: `AdminAuthenticate` middleware checks
  `session('admin_authenticated')`. `SESSION_DRIVER` was changed from
  `database` to `file` for this (see below) — sessions expire per
  `SESSION_LIFETIME` in `.env` (120 minutes by default) same as the rest of
  the app.
- **`robots: noindex, nofollow`** on the admin layout (`resources/views/layouts/admin.blade.php`)
  so the route doesn't get indexed if it's ever crawled — a defense-in-depth
  detail, not a substitute for keeping the slug private.
- **All admin actions operate on `localStorage`, not a server-side database**
  — there is no real backend data store yet (see DESIGN.md/MEMORY.md's "no
  database yet" notes). This means the admin panel's effects are scoped to
  whichever browser/device performed them, not a shared server-side dataset.
  This is consistent with the whole app's current architecture, not an admin
  panel–specific gap, but worth remembering: it does **not** give an operator
  real cross-device control over user data yet.

### Changed while building this: `SESSION_DRIVER`

Was `database` in `.env`. **Correction (2026-07-15, while building Google
login):** this file previously said no `sessions` table migration existed —
that was wrong. `database/migrations/0001_01_01_000000_create_users_table.php`
creates `users`, `password_reset_tokens`, *and* `sessions` together (Laravel
12's stock skeleton bundles them in one file); this was missed at the time
because the migration's filename only mentions `users`. The actual reason
`database` session storage would have failed at the time isn't relevant
anymore — `file` sessions work fine and there's no reason to switch back
just because the table exists. Leaving as `file` unless a real reason to
change it comes up (e.g. multi-server deployment needing shared session
storage).

## Google login — added 2026-07-15

Full feature/architecture writeup is in DESIGN.md; this is the
security-specific detail.

- **First real database-backed auth in this app.** Unlike the phone/OTP/MPIN
  flow (client-side simulated) and the admin panel (single shared password),
  this creates real `users` rows and a real Laravel session via
  `Auth::login()`. Treat `GOOGLE_CLIENT_SECRET` in `.env` with the same care
  as `APP_KEY`/DB credentials — already covered by the `.htaccess` deny
  rules from the incident at the top of this file, but worth naming
  explicitly since it's a new category of secret in this app.
- **Account-linking matches on `google_id` first, then falls back to
  `email`** (`GoogleAuthController::callback()`) before creating a new row —
  prevents a duplicate account if the same person's email later gets a
  password-based account through some other path. Standard, but worth
  knowing since it means **email is treated as a trusted match key** here;
  fine for Google (Google verifies the email itself), would need
  reconsidering if a non-verified-email provider were ever added the same way.
- **`linkPhone()` endpoint** (`POST auth/google/link-phone`) requires an
  active Google-authenticated session (`Auth::check()`) and rejects a phone
  already claimed by a different user (`User::where('phone', ...)->where('id', '!=', ...)`)
  — prevents one account from silently taking over another's phone-linked
  data. Standard Laravel validation (`digits:10`) on the input.
- **Google-only users get a random, hashed, never-surfaced password**
  (`Hash::make(Str::random(40))`) purely because the `password` column is
  `NOT NULL` on the stock `users` table — avoided a schema migration
  (`->change()`, which needs `doctrine/dbal`) for something they'll never
  use, not a real credential.
- **The redirect URI is a full explicit `.env` value, not derived from
  `route()`/`APP_URL`** — deliberate, see DESIGN.md. Means if this app's
  mount path or domain ever changes, `GOOGLE_REDIRECT_URI` must be updated
  *and* the corresponding entry in Google Cloud Console's Authorized
  Redirect URIs, or the OAuth flow will fail (Google rejects a mismatch).

## Deposit request IDs — UUID route keys (added 2026-07-16)

The admin panel's approve/reject actions (`POST /admin/deposits/{deposit}/approve`
and `/reject`) use Laravel implicit route-model binding on `DepositRequest`.
By default that binds on the auto-increment `id`, which put a sequential,
guessable integer directly in the URL/form action — enumerable (`id=1`,
`id=2`, ...) by anyone who could see the admin UI's HTML source, and a classic
IDOR smell even behind the auth gate.

Fixed by giving the model a second, opaque identifier used *only* for
routing:

- Migration `2026_07_16_150000_add_uuid_to_deposit_requests_table` adds a
  `uuid` column (unique, backfilled for any pre-existing rows).
- `DepositRequest::booted()` generates `Str::uuid()` on `creating()`, so
  every row gets one automatically — nothing in the controller changed.
- `DepositRequest::getRouteKeyName()` returns `'uuid'`, so `route('admin.deposits.approve', $deposit)`
  and implicit binding (`approveDeposit(DepositRequest $deposit)`) both use
  the UUID transparently. The auto-increment `id` still exists and is still
  used for everything internal (foreign keys, `latest()` ordering, log
  entries) — it just never appears in a URL.

**Verified**: creating a row and calling `route('admin.deposits.approve', $deposit)`
produces `.../deposits/5a39fb81-3615-4b91-9873-53b865b267f1/approve`, not
`.../deposits/1/approve`; `DepositRequest::where('uuid', ...)->first()`
resolves it correctly.

**Standard to apply to any future model that gets a URL/route param and is
looked up by a human-facing action (approve, reject, view, download, etc.)**:
give it a `uuid` column + `getRouteKeyName()` override the same way, rather
than exposing the raw primary key. This does **not** apply to values that
aren't identifiers at all — e.g. the Add Money page's `?amount=` query
param is a non-authoritative UI prefill (the server re-validates whatever
amount is actually submitted in the POST body), not a record lookup key, so
there's nothing to enumerate or tamper with by hashing it.

## Withdrawal requests + admin notification bell (added 2026-07-17)

`app/Modules/Withdrawals` mirrors the Deposits module in the opposite
direction: a user requests a cash-out to their own UPI ID, an admin reviews
and pays out manually, then approves (debiting `WalletBalance`) or rejects.
`WithdrawRequest` got `uuid` + `getRouteKeyName()` from the start (not
retrofitted) — see the section above, same reasoning.

- **Known tradeoff, same as Deposits, but higher stakes**: `/withdraw-money`
  is unauthenticated — the `phone` field is a plain form input, not derived
  from a logged-in session (this app's modules get no `auth` middleware by
  default; see the "General checklist" item above). For deposits, a spoofed
  phone is low-risk because nothing is credited until an admin matches a
  submitted UTR against a real incoming payment. For withdrawals the
  direction is reversed: **anyone who knows or guesses a victim's phone
  number can submit a withdrawal request against that phone, with their own
  payout UPI ID.** The balance check (`WalletBalance::balanceFor()`) only
  confirms the wallet *has* enough money — it does nothing to confirm the
  requester *is* the phone's owner. The only backstop today is manual admin
  review before approval; **the admin must verify the requester's identity
  out-of-band (matching phone to a known user) before approving, not just
  check the balance.** Before any real deployment, this route should require
  `Auth::check()` and take the phone from the authenticated user, not the
  request body.
- `AdminController::approveWithdrawal()` re-checks `WalletBalance::balanceFor()`
  against the request amount at approval time (not just at submission time),
  since the balance can move between submission and review — but this is a
  read-then-write with no row lock, so two approvals racing on the same
  under-funded wallet could still both pass the check before either debits.
  Acceptable for a single-operator admin panel reviewing requests one at a
  time; would need a DB-level lock (`lockForUpdate()`) under concurrent admins.
- **Notification bell** (`resources/views/components/admin-notification-bell.blade.php`,
  polling logic in `resources/js/admin.js`) is plain AJAX polling (every 12s)
  against `GET admin.notifications.poll`, not websockets/broadcasting — no
  new infrastructure, acceptable staleness for a single-operator tool. It is
  gated by `@if (session('admin_authenticated'))` in `layouts/admin.blade.php`,
  so the poll/read endpoints are only ever called once already logged in; the
  routes themselves still sit behind the `admin.auth` middleware group
  regardless, so this is defense-in-depth, not the only gate.
- `admin_notifications` has no per-recipient read-tracking (`read_at` is a
  single shared column) — correct for one admin, would need a pivot table if
  multi-admin auth is ever added (see the Ops console section above).

See [MEMORY.md](MEMORY.md) for the dated record of when security-relevant
changes were made.
