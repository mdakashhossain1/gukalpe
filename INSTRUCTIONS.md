# INSTRUCTIONS.md — Setup & Running

## First-time setup

```bash
composer install
cp .env.example .env        # if .env doesn't already exist
php artisan key:generate
touch database/database.sqlite   # DB_CONNECTION=sqlite in .env
php artisan migrate
npm install
```

## Running locally

Two supported ways to serve this app — use whichever fits what you're doing:

### Option A — `php artisan serve` (recommended for day-to-day dev)

```bash
composer run dev
```

This runs, concurrently: `php artisan serve`, the queue listener, `php artisan pail`
(log tailing), and `npm run dev` (Vite). Stops all four together on Ctrl+C.

### Option B — XAMPP, served directly from this folder

The app is reachable at `http://localhost/gullakpe/gullakpe-laravel/` because this
directory has its own front controller (`index.php`) and `.htaccess` that mirror
`public/index.php` / `public/.htaccess`. This exists so the app works even when
the Apache vhost/document root points at `htdocs/gullakpe/gullakpe-laravel/`
rather than its `public/` subfolder.

Requirements: Apache `mod_rewrite` enabled (on by default in XAMPP), assets built
via `npm run build` (or `npm run dev` running alongside for HMR).

**Do not delete the deny rules in this folder's `.htaccess`** — they're what stop
`.env`, `vendor/`, `storage/`, etc. from being directly downloadable now that this
folder is web-root-accessible. See [SECURITY.md](SECURITY.md).

## Building frontend assets for production

```bash
npm run build
```

## Tests

```bash
composer test
# or
php artisan test
```

## Related docs

- [AGENTS.md](AGENTS.md) — conventions for AI agents working in this repo
- [DESIGN.md](DESIGN.md) — design tokens, i18n mechanism, screens to port
- [SECURITY.md](SECURITY.md) — checklist to run after touching `.htaccess`/`index.php`
- [MEMORY.md](MEMORY.md) — dated log of what's changed and why
