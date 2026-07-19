# AGENTS.md — GullakPe Laravel App

Instructions for AI coding agents (Claude Code and others) working in this repository.

## What this project is

GullakPe is a "digital gullak" (piggy bank) savings/goals app for the Indian market
(Hindi + English UI). This folder (`gullakpe-laravel/`) is the Laravel rebuild of a
static HTML/JS prototype that lives one level up at `../index.php` (outside this
project). That prototype is the UI/UX source of truth — see [DESIGN.md](DESIGN.md)
before building any screen so the Laravel version matches it. See
[MEMORY.md](MEMORY.md) for the running log of what has actually been done so far —
read it first when resuming work.

The modular port (per `../implementation_plan.md`) has been done — see
MEMORY.md's 2026-07-15 (2) entry for exactly what moved where. It still needs
a manual browser pass (tab switching, EN/HI toggle, animations, visual diff
against the prototype) before it's considered fully verified.

## Stack

- Laravel 12, PHP 8.2+
- SQLite for local dev (`database/database.sqlite`), driver in `.env` via `DB_CONNECTION`
- Vite 7 + `@tailwindcss/vite` (Tailwind v4) — **not** the Tailwind CDN build the
  static prototype uses. Reconcile config differences per DESIGN.md when porting UI.
- `laravel/pail` for logs, `laravel/sail`, `laravel/pint` for style, Pest/PHPUnit for tests

## Architecture notes

- `routes/web.php` itself defines nothing — each module owns its own routes.
  `app/Providers/ModuleServiceProvider.php` auto-loads a module convention: any
  directory under `app/Modules/<Name>/` with a `routes.php` gets registered under
  the `web` middleware group automatically, and `app/Modules/<Name>/Views/` gets
  registered as a view namespace named `<Name>` (e.g. `Home::home`). There are
  seven modules today — `Home` (`/`), `Explore` (`/explore`), `Portfolio`
  (`/portfolio`), `Rewards` (`/rewards`), `Profile` (`/profile`), `PlanDetails`
  (`/plan-details`), and `Auth` (no route — its view is an overlay included on
  every page, not a standalone page). Add new features as modules under
  `app/Modules/` the same way, not as loose `app/Http/Controllers`.
- Every module controller returns the **same** `resources/views/pages/dashboard.blade.php`,
  which `@include`s all seven module views as siblings inside `layouts.app`. This
  is a deliberate single-page-app shell (client-side JS shows/hides `.tab-content`
  divs) — not a bug, don't "fix" it into one-view-per-controller without checking
  with the user first, since that would break the tab-switching behavior ported
  from the original prototype.
- Two front controllers exist on purpose (see [INSTRUCTIONS.md](INSTRUCTIONS.md) and
  [SECURITY.md](SECURITY.md)):
  - `public/index.php` — standard Laravel entry point.
  - `index.php` (this directory's root) — added so XAMPP can serve the app directly
    from `htdocs/gullakpe/gullakpe-laravel/` without pointing the vhost at `public/`.
    It also serves static files from `public/` directly (build assets, `lang/*.json`)
    since the `.htaccess` rewrite can't do this reliably at this mount depth — see
    SECURITY.md before changing either file.
    The accompanying root `.htaccess` **denies direct access** to `vendor/`, `storage/`,
    `.env`, etc. — never remove those deny rules without replacing them.
- `resources/js/modules/*.js` are loaded as ES modules from `resources/js/app.js`,
  in the same order the original inline `<script>` tags executed in. Any function
  called from more than one file (or from an inline `onclick=""` in a Blade view)
  **must** be attached via `window.fn = fn` — ES module top-level declarations
  don't leak to `window` the way classic `<script>` tags did. The codebase already
  follows this convention almost everywhere; check MEMORY.md's port entry for the
  one place it didn't (`openAuth`/`closeAuth`).

## Working conventions

- Don't hand-edit `vendor/` or `node_modules/`.
- Run everything through `composer run dev` for local work (serves app + queue
  listener + `pail` logs + `vite` concurrently) — see INSTRUCTIONS.md.
- When you change `.htaccess` or either `index.php`, re-verify with the curl/PowerShell
  checklist in SECURITY.md before considering the change done — a wrong rewrite rule
  can silently re-expose `.env`.
- Update [MEMORY.md](MEMORY.md) with a dated entry after any non-trivial change
  (new module, schema change, routing change, security fix). Keep entries short —
  this is a changelog for humans and agents, not a commit log (that's what `git log`
  is for).
- English/Hindi is a hard requirement for every user-facing string — see DESIGN.md
  for the exact mechanism to follow.
