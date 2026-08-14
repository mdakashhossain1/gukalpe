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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.2. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
