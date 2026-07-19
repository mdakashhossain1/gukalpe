# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Read these first

This repo already has a hand-maintained documentation set — read it before making changes, not just this file:

- **[MEMORY.md](MEMORY.md)** — dated, numbered log of every non-trivial change and *why* it was made. This is the single most important file for understanding current state: the app is mid-migration from a static JS/localStorage prototype to a real Laravel backend, feature by feature, and MEMORY.md is the only place that tracks which features have moved and which are still demo-only. Read the newest entries (top of file) first when resuming work.
- **[AGENTS.md](AGENTS.md)** — conventions for AI agents in this repo (module pattern, working rules).
- **[DESIGN.md](DESIGN.md)** — design tokens, i18n mechanism, per-feature UX/architecture writeups.
- **[SECURITY.md](SECURITY.md)** — known incident history and a checklist to re-run after touching `.htaccess` or either `index.php`.
- **[INSTRUCTIONS.md](INSTRUCTIONS.md)** — setup/run instructions (summarized below).

**Documentation drift warning**: AGENTS.md, DESIGN.md, SECURITY.md, MEMORY.md, and several inline code comments (e.g. `app/Modules/Auth/routes.php`) describe a `resources/js/modules/*.js` client-side layer (`auth.js`, `navigation.js`, `app-state.js`, etc.) that bridges server auth results into `localStorage`. **That directory does not currently exist on disk** — only `resources/css/app.css` is built by Vite now, and only a couple of inline `<script>` blocks remain (admin chart, notification bell). Don't trust these docs' file-level claims about the JS layer without checking current reality first (`find resources -type f`, `grep -r` for the symbol in question) — the underlying architecture decisions (module-per-feature, DB models replacing localStorage) they describe are still accurate even where the specific file paths are not.

## Commands

Run all commands from `gullakpe-laravel/` (this directory), not the repo root.

```bash
# First-time setup
composer install
cp .env.example .env   # if missing
php artisan key:generate
touch database/database.sqlite   # DB_CONNECTION=sqlite in .env
php artisan migrate
npm install

# Day-to-day dev — runs serve + queue listener + pail (logs) + vite concurrently
composer run dev

# Build frontend assets for production
npm run build

# Tests (whole suite)
composer test
# or
php artisan test

# Single test
php artisan test --filter=TestClassName
php artisan test tests/Feature/SomeTest.php

# Code style
vendor/bin/pint
```

Two ways to serve the app locally:
- **`composer run dev`** — `php artisan serve` + queue + `pail` + Vite, recommended for day-to-day work.
- **XAMPP direct** — reachable at `http://localhost/gullakpe/gullakpe-laravel/` because this folder has its own `index.php`/`.htaccess` mirroring `public/`. See "Dual front controller" below before touching either file.

## Architecture

**What this is**: GullakPe, a "digital gullak" (piggy bank) savings/goals app for the Indian market (Hindi + English UI). It's a Laravel rebuild of a static HTML/JS prototype (`../index.php`, one level up, outside this project) that remains the UI/UX source of truth for screens not yet ported — check DESIGN.md before building a new screen.

**Module system** (not stock Laravel conventions): `routes/web.php` defines nothing itself. `app/Providers/ModuleServiceProvider.php` scans `app/Modules/<Name>/` at boot and, for each directory found:
- registers `<Name>/routes.php` under the `web` middleware group automatically
- registers `<Name>/Views/` as a view namespace named `<Name>` (e.g. `Home::home`)

Current modules: `Home` (`/`), `Explore` (`/explore`), `Portfolio` (`/portfolio`), `Rewards` (`/rewards`), `Profile` (`/profile`), `PlanDetails` (`/plan-details/{plan}`), `Plans` (plan purchase), `Auth` (Google + phone/OTP/MPIN login — no page of its own), `Deposits` (`/add-money`), `Withdrawals` (`/withdraw-money`), `Notifications` (`/notifications`), `Admin` (ops console, slug configurable), `Settings`. Add new features as a new module under `app/Modules/`, not as loose `app/Http/Controllers`.

**Progressive JS → Laravel migration**: this is the app's central ongoing effort, and it means different features are at different maturity levels — check MEMORY.md before assuming a feature works one way or the other:
- **Real, DB-backed**: phone+OTP+MPIN auth (`users`, `phone_otps`), Google OAuth login, wallet deposits (`deposit_requests`, UUID route keys), withdrawals (`withdraw_requests`), plans/plan purchases (`plans`, `plan_categories`, `user_plans`), notifications (`user_notifications`, `admin_notifications`), app settings (`app_settings` — referral toggle, commission rate, deposit limits, read via `AppSetting::current()` and shared to views through an `AppServiceProvider` view composer).
- **Still localStorage/demo-only**: some Ops Console tooling (wallet adjustment, simulations, activity log) — deliberately not migrated yet.

**Dual front controller** (see SECURITY.md and INSTRUCTIONS.md before changing either):
- `public/index.php` — standard Laravel entry point.
- `index.php` (this directory's root) — lets XAMPP serve the app directly from `htdocs/gullakpe/gullakpe-laravel/` without pointing the vhost at `public/`. It also hand-serves static files (`public/build/*`, `public/lang/*.json`) using `SCRIPT_NAME` to compute the real mount prefix, because the `.htaccess` rewrite can't do this reliably at this nesting depth, and sets MIME types explicitly (`mime_content_type()` was serving `.js`/`.css` as `text/plain`).
- The root `.htaccess` **denies direct access** to `vendor/`, `storage/`, `.env`, `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `tests/`, `node_modules/`, etc. — this folder is otherwise web-root-accessible, so never remove these deny rules without replacing them. Re-verify with the checklist in SECURITY.md after any edit to `.htaccess` or either `index.php`.

**Admin panel**: reached at a configurable slug (`ADMIN_PANEL_SLUG` env var, default `admin` but deliberately meant to be changed — see `config/admin.php`), gated by the `admin.auth` middleware alias (`App\Http\Middleware\AdminAuthenticate`) and a single shared password (`ADMIN_PANEL_PASSWORD`) compared via `hash_equals`. Admin actions log to a dedicated `admin_security` log channel. Progressive lockout on repeated failed logins — see MEMORY.md entry "Admin login: progressive lockout".

**i18n**: English/Hindi is a hard requirement for every user-facing string. See DESIGN.md's "Internationalization" section for the exact mechanism before adding UI text — don't assume this is optional or add English-only strings.

**Stack**: Laravel 12, PHP 8.2+, SQLite for local dev (`database/database.sqlite`), Vite 7 + `@tailwindcss/vite` (Tailwind v4 — not the Tailwind CDN build the static prototype uses; reconcile config differences per DESIGN.md when porting UI), `laravel/socialite` for Google login, `laravel/pail` for logs, `laravel/pint` for style, PHPUnit/Pest-capable test setup (`tests/Unit`, `tests/Feature`).

## Working conventions

- Don't hand-edit `vendor/` or `node_modules/`.
- After changing `.env`, admin settings, or view composers, run `php artisan view:clear` before re-testing — stale compiled views are a recurring gotcha logged multiple times in MEMORY.md.
- When verifying a change, prefer real HTTP requests (`php artisan serve` + curl/PowerShell, or an actual browser) over Tinker — Tinker doesn't inject `$errors` or session state the way a real request does, which has produced false-alarm warnings logged in MEMORY.md more than once.
- Update MEMORY.md with a short dated entry after any non-trivial change (new module, schema change, routing change, security fix) — this is a log for reasoning/decisions, not a substitute for `git log`.
