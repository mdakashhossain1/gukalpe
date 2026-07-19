# DESIGN.md — GullakPe Design Reference

## Real phone authentication — added 2026-07-16

First feature in the "move off JS/localStorage onto real Laravel" push
(see MEMORY.md) that touches actual identity, not just a bolt-on feature
like Deposits. User confirmed scope first: real phone+OTP+MPIN auth with
a real `users` table and real Laravel sessions (not just moving the data
store while keeping "trust whatever phone the client sends" - the same
gap Deposits still has).

**Replaces**: the old client-side simulation entirely lived in
`resources/js/modules/auth.js` - OTP verification was a hardcoded
`enteredOtp === '123456'` check, MPIN was compared against a plaintext
value stored in the `gullakpe_users` localStorage blob, and "signup"
meant writing a JS object to localStorage. None of it touched a server.

**New real backend**:
- `users` table gained an `mpin` column (hashed via `casts()`, same
  convention as `password`).
- New `phone_otps` table (`App\Models\PhoneOtp`) - one row per phone,
  `otp_hash` (never plaintext), `expires_at` (5 min TTL), `attempts`
  (locks after 5 wrong guesses). `PhoneOtp::generateFor()` is the single
  point where a real SMS gateway (MSG91/Twilio/etc.) would plug in later -
  **there is no SMS provider configured**, so the generated code is
  flashed back to the `/login/verify-otp` page in a clearly-labeled
  "Demo mode" banner instead of being texted anywhere. This is the
  honest, pragmatic choice given no SMS credentials exist for this
  project; swapping in a real provider means changing only that one
  method.
- New module `app/Modules/Auth/Controllers/PhoneAuthController.php` +
  5 real Blade pages (`Auth::phone`, `verify-otp`, `set-mpin`, `mpin`,
  `forgot-mpin`), all extending the same JS-free `layouts.simple` layout
  Deposits already uses. Real routes: `GET/POST /login`,
  `/login/verify-otp`, `/login/set-mpin`, `/login/mpin`,
  `/login/forgot-mpin`, `POST /login/resend-otp`.
- **Flow** (mirrors the old modal's UX exactly, just as real page
  navigation instead of hidden `<div>` steps toggled by JS): phone number
  → if that phone already has an MPIN set, straight to MPIN entry (no
  OTP); otherwise OTP → (new users only) full name + set a 4-digit MPIN.
  Forgot-MPIN re-enters the same OTP step, then skips the name field on
  set-mpin since the account already exists. One deliberate small
  deviation from the old spec: the old flow had no name-collection step
  at all (new "users" got a random fake name generated client-side,
  e.g. "Karan Verma") - a **real** users table shouldn't be seeded with
  joke placeholder names, so `set-mpin` now asks for a real name for new
  signups.
- MPIN brute-force protection via `Cache` (5 wrong attempts → 15 minute
  lockout per phone) - simpler than the admin login's tiered lockout
  (see AdminController) but the same spirit; this is a lower-stakes
  surface than the admin panel so a flat lockout was enough.
- Real users created via this flow get a synthetic unique email
  (`{phone}@phone.gullakpe.local`) and a random hashed password (never
  used to log in) - same convention `GoogleAuthController` already
  established for Google-only accounts, reused rather than reinvented.
  Phone is the real unique key across both signup paths: submitting a
  phone that's already linked to a Google account (but has no MPIN yet)
  correctly routes through OTP → set-mpin on the *same* user row, not a
  duplicate.

**Bridging into the still-unmigrated rest of the app** - the single most
important design decision here. Every other feature (wallet balance,
rewards, referrals, navigation gating, `window.isLoggedIn()`,
`window.getPhoneKey()`) still reads entirely from `localStorage`/
`sessionStorage`, keyed by phone, written by `finalizeLogin()` in
`auth.js`. Rather than rewriting every one of those call sites to check
real server auth (a much larger, separate migration - see "not yet
migrated" below), this reuses the **exact bridge pattern
`GoogleAuthController` already established**: a successful real login
flashes `phone_auth_bridge` (`{phone, name, isNewSignup}`) into the
session; `layouts/app.blade.php` emits it as `window.__phoneAuthUser`
(same as `window.__googleAuthUser`); a new block at the end of `auth.js`
picks it up on `DOMContentLoaded`, creates the local `gullakpe_users`
record if missing (via the **existing** `saveUser()` - not reimplemented),
overwrites the name with the real one just collected, and calls the
**existing** `finalizeLogin()`. Net effect: the rest of the app keeps
working completely unchanged, now fed by server-verified data instead of
a JS mock. `window.openAuth()` (called from onclick handlers all over the
app to gate actions behind login) now simply navigates to the real
`/login` page (`window.location.href = window.__loginUrl`) instead of
opening the old modal.

**Deliberately left as dead code, not deleted, this pass**: the old
modal markup (`app/Modules/Auth/Views/auth-overlay.blade.php`'s
`#step-phone`/`#step-otp`/`#step-mpin`/`#step-login-mpin`/
`#step-forgot-phone` divs) and its driving JS in `auth.js`
(`handleContinueClick`, `handleVerifyClick`, `handleSetMpinClick`,
`handleLoginMpinClick`, `handleForgotPhoneClick`, `goToStep`,
`handleBackNavigation`, the OTP-input auto-advance listeners, etc.) are
now unreachable - nothing calls `openAuth()`'s old modal-opening behavior
anymore, so none of it ever runs. Not deleted in this same pass because
(a) this change was already large and unverified enough without also
doing a ~500-line deletion pass through tightly-coupled legacy code, and
(b) `finalizeLogin()`/`saveUser()`/`getUserDB()`/`closeAuth()` in the
same file are still very much alive (used by the new bridge and
elsewhere), so the cleanup needs care to remove only the truly-dead
parts. Flagged here explicitly as real follow-up work, not a gap
pretending to be finished.

**Not yet migrated** (unchanged from before this session's auth work,
still localStorage-based): wallet balance display, transaction history,
referral codes/commission, rewards claiming, the admin's "Wallet
adjustment" tool. These all still read/write `bachatpe_wallet_balance_
{phone}` etc. via the bridged phone number above - functionally
unaffected by this change, just not yet using the real `users` table or
a real session for anything beyond identity/login itself. Next planned
step per user direction: wallet balance + transaction history.

Verified end-to-end over real HTTP for every path: new-user signup
(phone → demo OTP → name + MPIN → real session + bridge script with
`isNewSignup:true`), returning-user login (phone → MPIN, both a wrong
attempt showing "Incorrect MPIN" and the correct one logging in with
`isNewSignup:false`), and the forgot-mpin flow's "no account found"
validation. Confirmed in the database directly: the user row is created
with a hashed MPIN and synthetic email, and the `phone_otps` row is
deleted after successful verification (not left around). All other pages
(Home, Explore, Portfolio, Rewards, Profile, Add Money, Ops console
routes) still return 200 after this change.

## Manual UPI Add Money — added 2026-07-16, rebuilt on real Laravel 2026-07-16

**First rebuild, same day**: originally built as a client-side modal
(`resources/js/modules/add-money.js` + `add-money-modal.blade.php`)
writing to `localStorage` keys mirroring the admin's `admin.js`. The user
asked for this to be real Laravel instead — a real database table,
controller, routes, and server-rendered Blade views, not JS/localStorage.
Both the old JS module and modal component were deleted outright, not kept
alongside the new version.

**Why this genuinely needed to be a database table, not just a style
preference**: an admin reviewing a deposit is, by definition, a different
browser/device than the user who submitted it. `localStorage` cannot
bridge that — the old JS version's admin panel could only ever have seen a
deposit request if the admin happened to share the exact same browser
profile as the submitter. This wasn't just "wrong structure," it was a
request that could never actually work cross-device.

**Architecture**:
- `deposit_requests` table (migration
  `2026_07_16_074536_create_deposit_requests_table.php`, model
  `App\Models\DepositRequest`) — phone-keyed (phone/OTP login in this app
  is entirely client-side, so there's no real `user_id` to key on without
  a much larger auth rework). `status` is `pending`/`approved`/`rejected`.
- `wallet_balances` table (migration
  `..._create_wallet_balances_table.php`, model `App\Models\WalletBalance`)
  — also phone-keyed. **This is a new, separate source of truth for
  deposit-driven (and now admin-manual-adjustment... see the Ops console
  section for why that one wasn't touched) wallet credits. It is NOT the
  same balance Home displays or that rewards/referral-commission claims
  write to** — those still use the pre-existing
  `bachatpe_wallet_balance_{phone}` localStorage key. Unifying the two was
  out of scope for this change (it would require either real server-side
  session auth for phone/OTP users, or a client-side bridge — the latter
  is exactly the "JS structure" the user asked to move away from for this
  feature). Flagging this explicitly rather than leaving it an
  undocumented surprise: **after this change there are two wallet
  balances in the system**, and a user who gets a deposit approved will
  not see it reflected on Home's balance display without further work.
- `app/Modules/Deposits/{Controllers/DepositRequestController.php,routes.php,Views/create.blade.php}`
  — `GET /add-money` (form), `POST /add-money` (validate + store). A
  **traditional full-page Laravel form**, not an AJAX/SPA modal — chosen
  explicitly over keeping the modal UX, per the user's direction. Real
  server-side validation (`phone` digits:10, `amount` numeric, `utr`
  digits:12 + uniqueness), flash-message success banner on the same page
  (`session('success')`), `@error` directives for field-level errors. No
  custom JavaScript on this page at all — it uses a dedicated
  `resources/views/layouts/simple.blade.php` layout that loads only
  compiled Tailwind CSS (`@vite(['resources/css/app.css'])`), not the
  SPA's `app.js` bundle.
- Home's amount-entry card + shortcut buttons were **not** rewritten —
  they still work exactly as before (JS-driven comma formatting, quick
  amounts). Only the "Add Money" button changed: it's wrapped in a real
  `<form method="GET" action="{{ route('deposits.create') }}">`, so
  clicking it is a genuine page navigation carrying the typed amount as a
  query string (`?amount=...`) — no fetch/JS bridge needed to get the
  amount across.

**UTR uniqueness** is enforced at the database level via a **partial
unique index** (`deposit_requests_utr_active_unique`, `WHERE status !=
'rejected'`), not a plain column-level `unique()`. This matters: a UTR is
a real bank transaction reference and must never be claimable twice while
*live* (pending or approved), but a *rejected* claim usually means the
claim was wrong (mistyped amount, etc.), not that the UTR itself is
fraudulent — a genuine resubmission after rejection must still work. The
controller's validation (`Rule::unique(...)->where(status != rejected)`)
mirrors the same scoping so the friendly validation message and the raw
DB constraint never disagree. **This partial-index approach is SQLite-only**
(this app's actual dev/prod database) — MySQL has no equivalent, so a
future move to MySQL would need the uniqueness check to live entirely in
application logic instead.

Verified end-to-end over real HTTP (not just unit-level): submitted a
UTR, confirmed the duplicate-UTR error fires on resubmission, logged into
the Ops console, approved the request via the real form button, and
confirmed `wallet_balances` was credited server-side. Also verified via
direct model calls that a *rejected* UTR can be resubmitted while a
*pending* one cannot be duplicated. A real bug was caught in this pass
(see MEMORY.md): `Deposits@if(...)` in the admin sidebar component had no
space before `@if`, which made Blade silently fail to compile that
directive — `php -l` doesn't catch this class of bug since Blade
directives aren't valid raw PHP; only an actual render/compile catches it.

## Motion & animation policy — established 2026-07-15, tightened 2026-07-16

This app ported a consumer/gamified prototype (confetti, flying coin
bursts, chime sounds, fake activity tickers, infinite pulsing glows) and
two passes have progressively stripped that back toward a "banking level,
minimal" feel: entry (8) in MEMORY.md removed springy/overshoot easing and
toned down the worst glows; entry (24) removed the rest of the
gamification layer outright (confetti, coin-burst physics + chime,
fake social-proof ticker, floating emoji badges, decorative particle
bursts, the app-wide tap-release glow halo) and converted several
"infinite" decorative animations to static or one-shot.

**Going forward, the rule for any new animation added to this app:**
- Loading/waiting states (spinners, skeleton shimmer, dots) may loop
  infinitely — that's the one legitimate use of perpetual motion, since it
  signals "still working."
- Everything else — entrances, confirmations, state changes — should be
  **one-shot**. If it's still animating after the state it signals is
  over, it's decoration, not signal, and doesn't belong here.
- No overshoot/spring easing (`cubic-bezier` values >1) — use
  `cubic-bezier(0.22, 1, 0.36, 1)` (ease-out-quint) for entrances instead.
- No confetti, particle bursts, chime sounds, or fabricated "live activity"
  content — a bank states facts, it doesn't celebrate or gamify them.
- Tap feedback should be the existing ripple + scale only, no added glow
  halo on top.

## Settings — merged into Profile, added 2026-07-15, folded in 2026-07-16

**Originally a separate 8th tab** (`app/Modules/Settings/`); the user later
asked for it to not be a separate section at all — each option should live
directly inside Profile instead. The dedicated tab
(`app/Modules/Settings/Views/settings.blade.php`), its route
(`GET /settings`), and its controller were deleted; the six option rows now
live as ordinary list items in `app/Modules/Profile/Views/profile.blade.php`
(same `Support & Others` list, same visual style as Bank account/Statements),
each still opening its own modal via `window.openSettingsModal(name)`. The
desktop sidebar's separate "Settings" nav item (and the divider above it)
was removed for the same reason — Profile is now the only entry point, on
both mobile and desktop.

**Explicitly a preview/showcase feature**, per the user's original request —
`window.saveSettingsModal()` shows a toast but doesn't persist anywhere real.
Six rows in Profile, each opening its own modal (`app/Modules/Settings/Views/modals.blade.php`,
the one file kept from the old module) via `window.openSettingsModal(name)`:
Profile Information, Security, Notifications, Language & Region, Payment
Methods, Privacy & Data. Modal shell is `.settings-modal`/`.settings-modal-card`
in `app.css`, a bottom-sheet on mobile that becomes a centered card at `sm:`.

**One exception to "not wired to anything real": Language** actually calls
the app's existing `window.toggleLanguage()` i18n system (see this file's
"Internationalization" section above) rather than faking it — there was no
reason to build a second, fake language switcher when a real one already
exists and the modal can just drive it.

**Toast notifications**: installed [Notyf](https://github.com/carlosroso/notyf)
(`resources/js/modules/toast.js`), per the user's explicit ask for "a toast
library" rather than extending the app's existing hand-rolled
`showGlobalSuccess()` toast (`auth.js`) or the admin panel's `#admin-toast`
div — those two continue to serve their original call sites unchanged;
Notyf is scoped to the new Settings page's save/delete actions
(`window.toast.success(...)` / `window.toast.error(...)`, exposed globally
for any future feature to reuse). Configured with two custom types (teal
success, red error) matching brand colors rather than Notyf's defaults.
Vite automatically extracts Notyf's CSS into its own chunk
(`app-KOmfmGu7.css` at last build) linked to the `app.js` entry in the
manifest — confirmed `@vite()` picks it up without any extra Blade markup
needed, since Vite tracks per-entry CSS dependencies itself.

**Found a pre-existing bug while first building this (now moot for Settings,
still real elsewhere)**: while Settings was still its own tab, its back
button was going to use `window.previousTab` (the same mechanism
`goBackFromPlanDetails()` in `animations.js` uses) but that's only ever
*set* inside a dead, commented-out block at the top of `navigation.js`
(`/* OLD BROKEN NAVIGATION DISABLED ... */`, lines 1-173) — so it's always
`undefined`, and `goBackFromPlanDetails()` silently always falls back to its
hardcoded default (`'explore'`). Predates the Laravel port entirely, unrelated
to Settings, and **still not fixed** — worth fixing on its own if
plan-details' back button is ever reported as going to the wrong place. Now
that Settings has no page/back button of its own (it's plain list items in
Profile), this specific instance of the bug is moot, but the underlying
`previousTab` mechanism is still broken for whatever else relies on it.

## Google login — added 2026-07-15

The first piece of this app that's genuinely server-side and database-backed
— everything before this was client-side/localStorage-simulated (see MEMORY.md's
"no database yet" notes). OAuth fundamentally requires a real server redirect
round-trip (browser → Google → back to a server route with an auth code), so
it couldn't be simulated client-side the way phone/OTP/MPIN was.

### The two-system bridge (why it exists)

This app has two parallel notions of "logged in":
1. **Real, server-side**: a `users` table row + a real Laravel session
   (`Auth::login()`), populated via Google OAuth
   (`app/Modules/Auth/Controllers/GoogleAuthController.php`, using Socialite).
2. **Simulated, client-side**: `localStorage['gullakpe_users']` keyed by phone
   number, which is what every existing feature (wallet, rewards, referral,
   portfolio) actually reads. This predates Google login and wasn't rebuilt.

Google gives a name/email/photo but **no phone number**, and the rest of the
app is phone-keyed. So the flow is:

- **Returning user** (already has a phone linked from a previous visit):
  `GoogleAuthController::callback()` flashes their profile
  (`session('google_user')`, includes `phone`) → `layouts/app.blade.php`
  emits it as `window.__googleAuthUser` → `resources/js/modules/auth.js`'s
  bridge (bottom of the file) sees `phone` is present and logs them straight
  into the existing localStorage system, no re-prompting.
- **First-time Google sign-in** (no phone on file yet): same flash/bridge,
  but with `phone: null`. The bridge stashes the Google profile
  (`sessionStorage['pendingGoogleLink']`) and opens the *existing* phone/OTP
  step to collect one — reusing that flow rather than building a new one.
  When it completes, `finalizeLogin()` (already the single choke point every
  login/signup path runs through) checks for the pending link, uses the real
  Google name/email instead of the random demo values the phone-only path
  generates, and calls a new endpoint
  (`POST auth/google/link-phone`) to persist that phone onto the real,
  server-side `users` row.

### Files

- `app/Modules/Auth/Controllers/GoogleAuthController.php` — `redirect()`,
  `callback()` (find-or-create by `google_id`, falling back to matching
  `email` before creating a new row, to avoid duplicates), `linkPhone()`.
- `database/migrations/2026_07_15_172555_add_google_oauth_fields_to_users_table.php`
  — added `google_id` (unique), `avatar`, `phone` (nullable, unique) to
  Laravel's stock `users` table (already existed from the base skeleton,
  never used until now — confirmed via `php artisan migrate:status` before
  adding anything, so as not to create a duplicate).
- `config/services.php` → `google` key; `.env` → `GOOGLE_CLIENT_ID`,
  `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`. The redirect URI is a full
  explicit URL, not derived from `APP_URL`/`route()` — `APP_URL` is just
  `http://localhost` while the app actually lives under
  `/gullakpe/gullakpe-laravel/`, so `route()` would generate a URI missing
  that prefix and Google would reject it as not matching the registered
  Authorized Redirect URI.
- The "Continue with Google" button in `auth-overlay.blade.php` (previously
  `onclick="alert('Google Auth triggered')"` — a stub) now navigates to
  `route('auth.google')`.

### Verified vs. not yet verified

Verified via HTTP with blank placeholder credentials: hitting `/auth/google`
correctly redirects all the way to `accounts.google.com`, which responds
with "Missing required parameter: client_id" — confirming the whole chain
(routing → Socialite → config → redirect construction) works; the only
missing piece is the real credential values. **Not yet verified**: the full
callback → user-creation → localStorage-bridge path end-to-end, since that
needs a real Google account completing a real consent screen, which needs
the real Client ID/Secret in `.env` first.

## Desktop layout system — added 2026-07-15

The app was mobile-only until this point (no `max-w`, no breakpoints
anywhere in the shell — confirmed by grep before starting). User asked for
a genuinely different desktop design, not a centered phone frame, across
Home and the other pages.

**Structural piece (applies everywhere, done to full depth):**
- `resources/views/components/desktop-sidebar.blade.php` — a persistent
  left sidebar (`hidden md:flex`, 248px wide, `fixed left-0`), replacing the
  bottom tab bar on desktop (`resources/views/components/bottom-nav.blade.php`
  gained `md:hidden`). Same 5 destinations (Home, Explore, Portfolio,
  Rewards, Profile). `resources/views/layouts/app.blade.php`'s `<main>`
  gained `md:ml-[248px]` to sit beside it, not under it.
- **Active-state sync between the two navs**: they can't share element IDs
  (duplicate IDs are invalid HTML), so `resources/js/modules/navigation.js`'s
  active-highlight logic was switched from matching `btn.id === 'nav-' + tabId`
  to matching a `data-tab="home"`-style attribute present on both the mobile
  buttons and the sidebar buttons. Sidebar's active row gets a background
  tint (`.sidebar-nav-item.is-active` in `app.css`) — **not** a colored
  left-border stripe, which is a banned "side-stripe accent" pattern (reads
  as a lazy AI-slop tell); a full-row tint is the standard sidebar
  convention (Linear/Notion/Vercel-style dashboards).

**Per-page content, two tiers, done honestly rather than claiming false parity:**
- **Home** got an actual bespoke reflow: the Plans section (3 goal cards)
  was a mobile horizontal-scroll carousel (`overflow-x-auto snap-x`,
  `min-w-[290px]` cards) — converted to a real 3-column CSS grid at `md:`
  (`md:grid md:grid-cols-3 md:overflow-visible md:snap-none`, cards get
  `md:min-w-0` to let grid sizing take over). This is the "genuinely
  different, not just wider" desktop treatment the user asked for.
- **Explore, Portfolio, Rewards, Profile, Plan Details** did **not** get the
  same depth of bespoke reflow. Investigated Explore's plan-card list
  specifically (`app/Modules/Explore/Views/explore.blade.php` ~line 651+):
  the cards are direct siblings in the page flow with no shared wrapper
  `<div>` around just the card list, so turning them into a grid the way
  Home's were would mean inserting a new opening/closing wrapper spanning a
  large, not-fully-read stretch of an 1806-line file — real risk of getting
  a tag boundary wrong with no browser available to catch it visually. None
  of these 5 pages had *any* `max-width` anywhere either, so on a desktop
  viewport they'd have stretched edge-to-edge to fill the space next to the
  new sidebar, which reads as broken, not designed. Given both the risk and
  the remaining time, applied the safe floor instead: a shared CSS rule
  (`.tab-content:not(#tab-home) { max-width: 860px; margin-inline: auto }`
  at `md:`, in `app.css`) centers all five at a sane reading width. The
  `:not(#tab-home)` exclusion matters — without it this rule would have
  fought Home's own wider `max-w-7xl` grid and silently shrunk it back down,
  since both share the `.tab-content` class.
- **Auth overlay** deliberately untouched — it's a login modal
  (`max-w-[400px]` card), and a wide desktop treatment doesn't apply to a
  modal dialog the way it does to a page.

**Honest gap**: a *true* bespoke multi-column dashboard reflow for the
other 5 pages (matching Home's depth) is real, separate follow-up work —
each page needs the same kind of structural investigation Explore's plan-card
list needed here, one page at a time, ideally with a browser open to verify
against. What exists now prevents the "stretched and broken" failure mode
everywhere and delivers the full bespoke treatment specifically on Home.

Source of truth for visual design is the static prototype at `../index.php`
(one level above this Laravel project, ~12k lines, not yet ported). This file
extracts the reusable tokens and conventions from it so Laravel views stay
consistent without needing to re-read the whole prototype every time.

## Brand tokens (from the prototype's `tailwind.config`)

```js
colors: {
  brand: {
    DEFAULT: '#0A5C66',   // primary teal — buttons, active states, links
    light:   '#118290',
  },
  bg: '#F4F7F8',           // app background
}
fontFamily: {
  sans:       ['Roboto', 'sans-serif'],           // default body text
  poppins:    ['Roboto', 'sans-serif'],           // headings, labels, buttons — utility kept the `poppins` name to avoid touching every blade file, but it resolves to Roboto now
  devanagari: ['Roboto', 'sans-serif'],           // Hindi text
}
```

**Updated 2026-07-18: Roboto-only, site-wide.** Self-hosted from the two
files the user supplied (`resources/fonts/roboto/Roboto-Regular.ttf` 400,
`Roboto-Light.ttf` 300 — no other weight/style file exists), declared via
`@font-face` in `resources/css/app.css` and wired into `--font-sans`,
`--font-poppins`, `--font-devanagari` (all three point at the same face now).
All Google/Bunny Fonts `<link>` tags were removed from every layout
(`layouts/app.blade.php`, `layouts/simple.blade.php`, `layouts/admin.blade.php`)
so nothing else can load a competing font. Two known tradeoffs, accepted
per explicit instruction ("no other fonts allowed"): (1) heavier weights used
throughout (`font-bold`/`font-black` etc.) are browser-synthesized faux-bold
since no true bold/black cut was supplied; (2) Roboto has no Devanagari
glyphs, so Hindi text silently falls back to the browser's default system
font for those characters — this is unavoidable without a Devanagari-capable
font file and is a browser behavior, not a second font being loaded.

Other recurring raw values seen throughout the prototype (not in the config,
but used directly as Tailwind arbitrary values): text color `#1a153a` (dark
headings), disabled state `#a5a3b2`, card shadow
`shadow-[0_4px_20px_rgba(0,0,0,0.03)]`, "premium" shadow
`shadow-[0_4px_12px_rgba(10,92,102,0.2)]`.

Icons: FontAwesome 6.4 (`fa-regular`/`fa-solid` classes) + Lucide (`lucide` script,
inline `<i data-lucide="...">` pattern). Motion: anime.js 3.2.1 for JS-driven
animations, plus a custom Tailwind keyframe:

```js
keyframes: { shake: { '0%,100%': 'translateX(0)', '25%': 'translateX(-5px)', '50%': 'translateX(5px)', '75%': 'translateX(-5px)' } }
animation: { shake: 'shake 0.4s ease-in-out' }  // used on form validation errors
```

### Reconciling with this Laravel app

The prototype loads Tailwind via the CDN script (`cdn.tailwindcss.com`) with an
inline JS config — fine for a throwaway prototype, wrong for production. This
Laravel app compiles Tailwind v4 through Vite (`@tailwindcss/vite`, configured in
`vite.config.js`, entry `resources/css/app.css`). When porting a screen:
translate the CDN `tailwind.config` theme extension above into this project's
Tailwind v4 CSS-based config (`@theme` in `resources/css/app.css`), don't add a
second CDN `<script>` tag to Blade views.

**Hand-written CSS in `app.css` must go inside `@layer components { ... }`
(or `@layer base` for element resets), never as bare/unlayered CSS.** Tailwind
v4 generates its own output inside named CSS Cascade Layers
(`theme, base, components, utilities`), and per spec, unlayered CSS always
beats layered CSS at equal specificity regardless of source order — so
unlayered custom classes silently override Tailwind utility classes on any
element that has both. This bit us for real: `.interactive-element`'s
`position: relative` (added by `initPremiumInteractions()` for the ripple
effect) was overriding `absolute` on every interactive button in the app,
which is why the login overlay's back/close buttons rendered stacked instead
of pinned to opposite corners. See MEMORY.md's 2026-07-15 (4) entry for the
full diagnosis. This wasn't a problem in the original CDN-based prototype,
which doesn't use cascade layers the same way.

## Internationalization (English / Hindi) — required for every screen

The prototype implements i18n client-side and this behavior must be preserved
(or deliberately re-implemented server-side — decide explicitly before porting,
don't silently drop it):

- Library: `i18next` + `i18next-http-backend` (loaded via `unpkg` in the
  prototype; consider bundling via npm instead in this project).
- Dictionaries are flat JSON files keyed by the **literal English source string**,
  not semantic keys — e.g. `{"Add Money": "Add Money"}` in `en.json` and
  `{"Add Money": "पैसे जोड़ें"}` in `hi.json`. These already exist at
  `../en.json` and `../hi.json` (project root, outside this Laravel app) and are
  the agreed source of truth to reuse — do not invent a new translation format
  without checking with the user first.
- Runtime behavior: on `DOMContentLoaded`, i18next initializes with the language
  from `localStorage['gullak_lang']` (default `'en'`), loads `./{{lng}}.json`,
  then walks every text node under `<body>` and replaces its text if
  `i18next.exists(originalEnglishText)`. Toggling is done via
  `window.toggleLanguage()`, which flips the language, updates
  `localStorage['gullak_lang']`, and fades `document.body` opacity during the
  swap. A toggle button (`#lang-toggle-btn`) shows both an `.lang-en` and
  `.lang-hi` span and highlights whichever is active.
- **Resolved (2026-07-15): kept the exact client-side approach.** `en.json`/
  `hi.json` are served from `public/lang/` (fetched by i18next at
  `/lang/{{lng}}.json` — the original `'./{{lng}}.json'` was relative and broke
  once real routes like `/explore` existed, since it resolved against the
  current URL path instead of the site root). They're also copied to
  `resources/lang/` matching the plan's architecture diagram, but that copy is
  unused for now — no `__()` server-rendered strings yet. If server-side
  translation is ever wanted, that's a deliberate separate decision, not a
  silent addition on top of this.

## Key screens/flows present in the prototype (ported 2026-07-15)

All of the below were extracted byte-for-byte into their module views (see
MEMORY.md's port entry for exact file mapping). Kept here as a map of what
should exist — if you're touching one of these areas, this tells you which
module owns it, and the original `../index.php` is still the reference to
diff against if something looks off:

- **Auth**: phone number entry → OTP verification → MPIN setup → MPIN login →
  forgot-phone recovery. Includes resend-OTP timer, max-attempts lockout modal,
  shimmer/loading button states.
- **Global loader / splash screen** shown while the dashboard boots.
- **Dashboard (`tab-home`)**: wallet balance card, goal growth chart, "today"
  stats, claim rewards, quick-add-amount, explore goals grid, trending goal
  (e.g. "Dream Superbike"), gold SIP goal card ("24K Pure Gold", "Start with
  just ₹100"), VIP badge.
- **Pull-to-refresh** on the dashboard.

Read the relevant section of `../index.php` directly when implementing one of
these rather than relying only on this summary — this file is a map, not a spec.

## Ops console (admin panel) — added 2026-07-15

A separate, desktop-oriented admin tool, deliberately **not** styled or built
like the mobile SPA shell — no bottom-nav, no global loader, no tab-switching.
It exists because the earlier debug modal (a hidden "Admin Settings" button in
Profile, see MEMORY.md's 2026-07-15 (9) entry) was removed as a genuine
security problem — wallet/referral-manipulation tooling with no auth, sitting
in a banking-portal app. This is that functionality rebuilt properly.

### How it's reached

- **URL is configurable, not hardcoded**: the route segment comes from
  `ADMIN_PANEL_SLUG` in `.env` (read via `config('admin.panel_slug')` in
  `app/Modules/Admin/routes.php`). Change the env value any time — no code
  change, no redeploy of routes, just edit `.env`. Never set it to `admin` or
  anything guessable; the slug being obscure is one of two protection layers.
- **Real login gate, not obscurity alone**: `ADMIN_PANEL_PASSWORD` in `.env`
  gates a login form (`app/Modules/Admin/Views/login.blade.php`). Compared via
  `hash_equals()` in `AdminController::authenticate()` (timing-safe, but see
  SECURITY.md for why this isn't a bcrypt hash and what that tradeoff means).
  Successful login sets `session(['admin_authenticated' => true])`;
  `App\Http\Middleware\AdminAuthenticate` (aliased `admin.auth`) protects
  `/{slug}/dashboard` and `/{slug}/logout`, redirecting to login otherwise.
  Login attempts are rate-limited (5 per IP per 60s, via Laravel's
  `RateLimiter`).
- Routes: `GET /{slug}` (login form), `POST /{slug}` (authenticate),
  `GET /{slug}/dashboard` (protected), `POST /{slug}/logout` (protected).
  None of the sub-paths say "admin" either — `dashboard`/`login`/`logout` are
  generic enough not to fingerprint the route as an admin panel.

### Design approach

Same brand tokens as the mobile app (`--color-brand` `#0A5C66`, Inter for
body/UI, Poppins for headings/labels) but a completely different visual
register — this is a **product/admin surface**, not the consumer mobile app:
fixed `rem` type scale (no fluid `clamp()`), restrained color (white surface,
teal used only for primary actions and the active toggle, not decoration),
no idle/decorative motion. Page title says "Ops Console", not "Admin" —
consistent with keeping the word "admin" out of anything user-visible, not
just the URL. Layout lives in `resources/views/layouts/admin.blade.php`
(separate from `resources/views/layouts/app.blade.php`, the mobile shell)
and pulls its own Vite entry (`resources/js/admin.js` /
`resources/js/modules/admin.js`) rather than the mobile app's bundle, so it
never loads bottom-nav/i18n/global-loader JS it has no use for.

**Sidebar navigation (added 2026-07-15, replacing the original 2-column
grid)**: with 4 distinct feature areas, a persistent sidebar
(`.ops-nav-item` buttons, `data-panel="wallet|simulations|settings|logs"`)
showing one panel at a time reads better than a single long scrolling grid.
Same background-tint active-state convention as the main app's desktop
sidebar (`.ops-nav-item.is-active` in `app.css`) — no left-border accent
stripe. Below `md:`, there's no room for a persistent sidebar, so it's
replaced by a horizontally-scrollable row of the same 4 buttons in the
mobile top bar — same `data-panel` attributes, same click handler
(`resources/js/modules/admin.js`, top of the `DOMContentLoaded` block)
drives both.

### Every feature, what its buttons do

All state lives in the same `localStorage` keys `resources/js/modules/app-state.js`
already uses (this app has no real database yet — see the "no database yet"
warning in `implementation_plan.md`/MEMORY.md). Implemented in
`resources/js/modules/admin.js`.

1. **Wallet adjustment** (`#wallet-adjust-form`) — enter a 10-digit phone
   number + a ₹ amount (negative to subtract), submit. Writes directly to
   `bachatpe_wallet_balance_{phone}`, clamped to never go below 0. Logs the
   change to the Referral log panel. This one is a faithful match to how the
   mobile app itself reads/writes wallet balance.
2. **Referral program toggle** (`#setting-referral-enabled`) — a switch,
   applies immediately (no save step). Writes `gullakpe_admin_referral_enabled`
   (`'false'` = off; anything else/absent = on), which is exactly what
   `checkAndProcessReferral()` in `app-state.js` already checks before
   processing any real referral.
3. **Program settings** (`#settings-form`) — cashback amount (₹),
   commission percent (%), settlement time. Save writes
   `gullakpe_admin_cashback_amount` / `gullakpe_admin_commission_percent` /
   `gullakpe_admin_settlement_time` — the same keys the mobile app's referral
   logic already reads (with the same fallback defaults: ₹100, 5%, `00:00`).
4. **Deposit requests** (added 2026-07-16, **rebuilt as a real Laravel page
   the same day** — see "Manual UPI Add Money" above) — unlike the other
   panels in this list, this is **not** a `[data-panel]` JS-tab within
   `/dashboard`; it's a real separate route (`GET /{slug}/deposits`,
   `App\Modules\Admin\Controllers\AdminController::deposits()`, view
   `Admin::deposits`). Pending/Approved/Rejected filter tabs are plain
   `<a href="?status=...">` links (server re-renders the filtered list),
   not JS. Approve/Reject are real `<form method="POST">` submissions
   (`admin.deposits.approve`/`admin.deposits.reject` routes) - Approve
   calls `WalletBalance::credit()` (the new DB-backed wallet, not
   localStorage) and sets the deposit `status` to `approved`; Reject sets
   it to `rejected`, which the database's partial unique index then
   allows to be resubmitted. A pending-count badge shows on the nav item,
   computed server-side and passed to the shared
   `resources/views/components/admin-sidebar.blade.php` component (used
   by both this page and `/dashboard`, so the sidebar doesn't need to be
   duplicated across the two).
5. **Simulations** (`#btn-simulate-commission`, `#btn-simulate-referral`) —
   **honest caveat**: these two are a simplified, from-scratch
   reconstruction, not a restoration of the original modal's exact logic.
   That logic (self-referral blocking, KYC-match blocking, a structured
   pending/available commission ledger per referral code) was deleted from
   `app-state.js` before this page existed and was never in version control,
   so it couldn't be recovered byte-for-byte. What's here instead: both
   buttons operate against a clearly-labeled `demo-referrer` wallet (never a
   real user's), using the current cashback/commission settings, and write
   log entries in the same message format/tone the original used. Good
   enough to demo the log/settlement flow end-to-end; not a claim that it
   reproduces every original business rule.
6. **Activity logs** — two tabs (Referral / Commission,
   `[data-log-tab]`/`[data-log-panel]`), reading
   `gullakpe_admin_referral_logs` / `gullakpe_admin_commission_logs` (JSON
   arrays, newest first, capped at 100 entries — same cap the original
   used). "Clear" wipes both after a confirm dialog. Deposit request
   approve/reject actions do **not** log here anymore (a "Deposits" tab
   existed briefly during the JS-based version and was removed when that
   moved to Laravel) — the `deposit_requests` table's own `status` +
   `reviewed_at` columns, browsable via the Deposit requests page's own
   Pending/Approved/Rejected filters, are the real audit trail now.

### Verification done vs. not done

Verified via HTTP: login page loads, wrong password rejected with the
Laravel-validation-error path, correct password sets the session and unlocks
`/dashboard`, unauthenticated access to `/dashboard` redirects to login,
`npm run build` succeeds with `admin.js` as its own bundle. **Not verified**:
the actual button/localStorage interactivity — that needs a real browser,
which wasn't available in the environment this was built in. Test the four
features above in an actual browser before relying on this for anything.
