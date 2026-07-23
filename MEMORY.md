# MEMORY.md — Project Log

## 2026-07-23 — Redesigned Explore Goal Plan cards to match reference screenshot

Redesigned the plan cards layout in `app/Modules/Explore/Views/explore.blade.php` to match the exact visual style from the user-provided screenshot.

- **Layout & Badges**:
  - Top row: Soft pastel marketing badge on left (`#FFF4E5`/`#ECFDF5`/`#F3E8FF`) and category tier badge on right (`STARTER`/`BEGINNER`/`PREMIUM`).
  - Card Body (Responsive horizontal grid on both mobile & desktop):
    - **Left**: Prominent circular icon pod (`bg-[#F2F7F8] rounded-full`, enlarged `w-18 h-18` on mobile with `text-[32px]` icon / `w-12 h-12` image, matching sample image).
    - **Center**: Title & subtitle (`truncate` on mobile), 3-column metrics grid with vertical divider lines (`border-r border-slate-200`) for *Interest Rate (Yearly)*, *Total Return*, and *Duration* (compact `text-[7.5px]` labels and `text-[15px]` values on mobile to avoid line breaks), plus *End-to-End Encryption* and *100% Trusted & Secure* trust indicators.
    - **Price & CTA**: Integrated mobile bottom flex row with compact price (`text-[18px]`) and deep teal `Buy Now →` button (`bg-[#0A5C66]`), keeping full desktop style on desktop (`md:` breakpoint).
- **Verification**: Recompiled Vite assets (`npm run build`), cleared view cache (`php artisan view:clear`), and tested with `php artisan test` (25 passed, 79 assertions).


User supplied an exact typography spec (Inter, weights 400/500/600/700/800) for a plan/investment screen and asked to use it as the only font site-wide, then said to source the files from Google Fonts. Replaces the 2026-07-18 (entry 44-ish) Roboto-only setup — same "no other fonts, no CDN link" policy, different font.

- Fetched Google Fonts' `css2` API for `Inter:wght@400;500;600;700;800` to discover the actual `fonts.gstatic.com` file URLs, then downloaded the two files it resolved to (`latin` + `latin-ext` subsets) — these are Inter's *variable* font files (a single file serves the whole `100 900` weight axis; Google's CSS just repeats `@font-face` blocks with the same `src` per weight for older-browser compatibility). Saved as `resources/fonts/inter/Inter-Variable-latin.woff2` and `Inter-Variable-latin-ext.woff2`; deleted the old `resources/fonts/roboto/` files entirely.
- `resources/css/app.css`: replaced the Roboto `@font-face` pair with two Inter `@font-face` rules (`font-weight: 100 900`, one per unicode-range subset), repointed `--font-sans`/`--font-poppins`/`--font-devanagari` and `body`'s `font-family` at `'Inter'`, and replaced the 8 hardcoded `font-family: 'Poppins', sans-serif` declarations (Plan Details buy-bar/stat-card/slide-invest styles) with `var(--font-sans)`.
- `app/Modules/Auth/Views/auth-overlay.blade.php`: two inline `style="font-family: 'Roboto', sans-serif"` swapped to `'Inter'`.
- Because Inter's files are a true variable font (unlike Roboto's single static weight/style cuts), `font-medium`/`font-semibold`/`font-bold`/`font-extrabold` now render as real distinct weights instead of the browser's faux-bold synthesis the old Roboto setup had — a tradeoff called out and accepted in DESIGN.md that no longer applies. Devanagari/Hindi fallback-to-system-font tradeoff is unchanged (Inter has no Devanagari glyphs either).
- Ran `npm run build`; confirmed the compiled `public/build/assets/app-*.css` has zero `Roboto` references and pulls in the two Inter woff2 files.
- Updated DESIGN.md's font section to match.

## 2026-07-19 (47) — Auth flow: auto-submit + button loading animation on MPIN/OTP entry

User wanted a "little bit of animation" across all 5 auth screens (`phone`, `verify-otp`, `mpin`, `set-mpin`, `forgot-mpin`): typing the last digit of an MPIN/OTP should visibly kick off "verifying", and clicking any auth "next" button should show it's working, not just sit there until the next full-page load arrives.

- **`resources/views/components/pin-input.blade.php`**: new `autoSubmit` prop → renders `data-auto-submit` on the group. Component's existing delegated `input`/`paste` listeners now also call `maybeAutoSubmit(group)` after syncing: once the hidden value's length equals box count, adds a `.pin-group-complete` class (triggers a brief scale-pulse, see app.css `pin-box-complete` keyframe) and calls `form.requestSubmit()` ~220ms later. Not applied blindly to every pin group - `set-mpin.blade.php` has two groups (new MPIN, confirm MPIN) and only the second (`mpin_confirmation`) auto-submits, since filling the first is only half the form.
- **`resources/views/layouts/auth-hero.blade.php`** (shared by all 5 screens): added a delegated `submit` listener - on any form submit (manual click or the above `requestSubmit()`), finds that form's submit button(s) (nested, or elsewhere via `form="<id>"` - this app puts action buttons in a separate bottom bar, not nested), disables them and swaps their content to a spinner (`fa-circle-notch fa-spin`) + a `data-loading-text` label (e.g. "Verifying...", "Sending OTP...", "Please wait..."). No reset-on-failure path needed - these are real full-page POSTs, so a validation failure just reloads the page fresh.
- Applied `:auto-submit="true"` to: `verify-otp` (6-digit OTP), `mpin` (login MPIN), `set-mpin`'s confirmation group only. Added `data-loading-text` to every submit button across all 5 pages, including the separate "Resend OTP" form.
- Verified: full test suite still green (25 passed), rebuilt assets, and confirmed via a real HTTP request to `/login` that the script and `data-loading-text` attribute render correctly in the live page.

## 2026-07-19 (46) — Removed redundant top toast on phone/OTP/MPIN auth screens

User pointed out the top-of-screen toast on the login flow duplicated the validation error already shown inline under the input (`@error` message beneath the phone/OTP/MPIN field), so the extra toast added nothing.

- **Fix**: removed `<x-toast />` from `resources/views/layouts/auth-hero.blade.php` only - the shared layout for `phone`/`verify-otp`/`mpin`/`set-mpin`/`forgot-mpin`. Left `<x-toast />` in place on `layouts/app.blade.php`, `layouts/simple.blade.php`, and `layouts/admin.blade.php`, which don't have inline error equivalents and still depend on it for `session('success')`/`session('error')` flashes (e.g. Deposits, Withdrawals).
- **Checked before removing**: `PhoneAuthController::resendOtp()` flashes `session('success', 'A new OTP has been generated.')` on the resend-OTP action - the only success-toast usage on these screens. Confirmed it's not the only feedback for that action: `verify-otp.blade.php` already renders a separate always-visible `session('demo_otp')` banner with the fresh code in demo mode, so nothing goes silently unconfirmed.
- Rebuilt assets (`npm run build`) and `php artisan view:clear`, then verified via a real HTTP request to `/login` that `toast-item`/`x-toast` no longer appears in the rendered markup.

## 2026-07-19 (45) — Stale `public/hot` file was serving dead Vite dev-server URLs

User rebuilt CSS (`npm run build`) after editing several Auth view/component blade files, but the site still showed no styling. Build itself was fine (manifest + hashed `app-*.css` regenerated correctly) - the actual cause was a leftover `public/hot` file from an earlier `composer run dev` / `npm run dev` session that hadn't been cleaned up. Laravel's `@vite()` directive checks for that file's existence to decide dev-server-mode vs manifest-mode; with it present, every layout emitted `<link>`/`<script>` tags pointing at `http://[::1]:5173/...` (the Vite dev server) instead of `public/build/assets/...` - and since nothing was listening on 5173, all CSS/JS silently failed to load with no console-visible 404 pattern most people think to check first.

- **Fix**: deleted `public/hot`, re-verified via a real HTTP request (curl/`Invoke-WebRequest` against the running site, not Tinker) that the rendered `<link rel="stylesheet">` now points at the hashed `public/build/assets/app-*.css` file.
- **Gotcha for next time**: if `composer run dev`/`npm run dev` ever exits uncleanly (killed process, terminal closed without Ctrl+C), `public/hot` can survive the process and keep pointing production traffic at a dev server that's no longer running. If a `npm run build` doesn't seem to take effect, check for `public/hot` before suspecting the build itself.

## 2026-07-19 (44) — Reduced login page top vertical space

The user requested reducing the space from the top on the login page.
- **Component**: Modified [auth-hero.blade.php](file:///C:/xampp_8.2/htdocs/gullakpe/resources/views/components/auth-hero.blade.php) to reduce the header height from `h-[300px]` to `h-[220px]`. This applies across all full-bleed auth screens (phone login, OTP verification, and MPIN forms), bringing the white card sheet up and resulting in a much tighter, more balanced, and premium design on mobile devices.
- **Assets**: Rebuilt client production assets via `npm run build` to compile the new `h-[220px]` class.

## 2026-07-19 (43) — SIP-style top-up pots: cumulative investment, one shared maturity date


Follow-up correction to entry (42). User clarified the flexible-amount slider (42) only covers a *one-time* purchase - what they actually meant, matching the earlier reference screenshot's "Setup SIP" tab (as opposed to its "One Time" tab, which is what (42) built): a user should be able to make repeated contributions into the SAME plan over time (invest ₹2000, later add another ₹2000 or ₹4000, etc.) up to an admin-configured max, and get one return at the end computed on whatever the cumulative total turned out to be - "not like just a one-time investment... just ended, not like this." Confirmed the exact mechanics via 3 clarifying questions before touching code (all "Recommended" answers): one shared maturity date for the whole pot (not per-contribution), top-ups simply stop once the max is hit (pot keeps accruing to maturity, doesn't close early), and this is a separate admin-toggled mode alongside (not replacing) the one-time flexible slider from (42).

- **Schema**: `plans.allow_topups` (bool, default false - only meaningful on top of a real min/max range; `Plan::isTopupPot()` = `allow_topups && isFlexibleAmount()`). New `plan_topups` table (`user_plan_id`, `amount`, timestamps) - a per-contribution audit trail, since the UserPlan row itself only ever holds the current *cumulative* `invested_amount`/`daily_profit_val`/`total_return` and would lose each individual top-up's amount/date the moment the next one landed.
- **`UserPlan::activePotFor($user, $plan)`**: the one open, not-yet-matured holding (if any) a user has for a plan - deliberately excludes anything past `matures_at` even if the scheduler hasn't swept it yet, so a late-arriving contribution after maturity correctly opens a *new* pot instead of silently re-extending a finished one's shared end date.
- **`PlanPurchaseController`**: branches at the very top - `isTopupPot()` + an existing active pot routes to a new `topUp()` method instead of the normal fresh-purchase path. `topUp()`: validates only that `currentTotal + amount <= max` (the per-contribution amount itself doesn't need to individually clear `min_investment_amount` - only the pot-opening contribution does), debits just the new amount (not the running total), recomputes `total_return`/`daily_profit_val` against the *new cumulative total* using the duration that was already locked in when the pot first opened, and leaves `purchased_at`/`matures_at`/`plan_duration_id` completely untouched. Referral commission is only ever evaluated on the branch that opens a brand new pot, never on `topUp()` - otherwise every top-up would incorrectly look like "the user's first-ever purchase" to that check and re-pay commission each time.
- **UI** (`PlanDetails::plan-details`): once `$activePot` exists, replaces the "Choose Your Investment" initial slider with a "Your Investment Pot" status card (progress bar toward max, current total, projected return, maturity date) plus a smaller "Add More" slider (same live-JS pattern as (42), scoped to the *remaining* room under the cap) - or a "Maximum Investment Reached" state with no add-more control once the cap is hit. The bottom Invest button relabels to "Add ₹X to Investment" / disables entirely when full.
- **Popup**: `purchase-success-popup` gained an `is_topup` branch ("Top-Up Successful", added-now vs total-invested vs projected-return-at-maturity), checked before the existing plan_type branches so it applies regardless of plan type.

Verified with `tests/Feature/TopUpPotPlanTest.php` (8 tests) - same UserPlan row reused across contributions (not a new row per top-up), maturity date provably unchanged after a top-up (`Carbon::travel()` + timestamp comparison), return on 3x the cumulative amount is exactly 3x the return promised on the first contribution alone, cap enforcement (exact-cap allowed, over-cap rejected), each top-up debits only its own amount, maturity correctly credits the *full* cumulative total from all contributions combined, a contribution arriving after a stale matured-but-unswept pot opens a genuinely new pot, and a real view-render assertion confirming Plan Details actually swaps from the initial slider markup to the pot-status markup once a pot exists. Full suite: 25 tests, 79 assertions, all green - zero regression on (42)'s one-time flexible purchases or any fixed-amount plan.

## 2026-07-19 (42) — Flexible investment amount: real drag-slider, proportional return calculation

plans.md's admin-controls list specifies Min/Max Investment as a real range (its "Premium Plan" example: "Minimum Investment ₹500 Maximum Investment ₹50,000"), separate from Trust Builder/Growth's explicitly-fixed single amounts - this had never been built. User pointed at a reference screenshot (a gold-SIP app's amount slider: drag → instant recalculated projected return) and asked for the same interface and logic: invest any amount within an admin-set range, return computed live as amount x admin-configured percentage.

**Architecture decision**: a true continuous drag-slider with instant live arithmetic cannot be done in pure CSS (every other interaction on Plan Details - tabs, FAQ accordion, the existing fixed-amount duration calculator - deliberately stays JS-free). Confirmed with the user this is a deliberate, scoped exception before building it: real vanilla JS for this one widget only, same precedent as the admin panel's icon-picker.

- **Schema**: `plans.min_investment_amount`/`max_investment_amount` (both nullable - every existing plan leaves them unset and keeps its old fixed-amount behavior exactly). New `Plan::isFlexibleAmount()`: true only when both are set and max > min (min==max would be a pointless "range"). `user_plans.total_return` - a per-holding snapshot, needed because for a flexible purchase the shared `plan_durations.total_return` is calibrated for one specific reference amount and can't be reused for whatever amount a different user actually picked.
- **Purchase logic**: `PlanPurchaseController` branches on `isFlexibleAmount()`. Flexible: validates a submitted `amount` against `[min, max]`, computes `total_return = amount * (1 + growth_rate/100 * duration_days/365)` from the selected duration's own `growth_rate`, snapshots that onto `user_plans.total_return`. Fixed: 100% unchanged, still uses the duration's precomputed figures.
- **`UserPlan::currentHolding()`** (Portfolio value, maturity crediting, daily-returns email) now has a 3-level fallback: per-holding `total_return` snapshot (flexible purchases) → `planDuration->total_return` (fixed, real duration) → `plan->total_return` (legacy, no duration). Same fallback applied to `chartPointsFor()` and `SendDailyReturnsEmail` (entry 40), which had the older 2-level version.
- **UI** (`PlanDetails::plan-details`, flexible plans only): "Choose Your Investment" card - duration pills (now real `<button>`s with a JS click handler instead of the fixed-plan's radio+`:has()` labels, since they have to drive live recalculation), a draggable `<input type=range>` plus +/- steppers, and live-updating Expected Return / Daily Growth / Maturity Date / Portfolio Preview / Invest-button amount, all recomputed client-side on every `input` event using the currently-selected duration's `data-rate`/`data-days`. A hidden hardcoded `#pd-flex-amount-input` (`form="plan-purchase-form"`) carries the chosen amount into the real POST on submit - the JS never talks to the server, it only decides what gets submitted.
- **Admin**: `min_investment_amount`/`max_investment_amount` fields added to the Plan form (`gt:min_investment_amount` validation), with inline copy explaining the range needs a real duration option to actually show the slider.

Verified three ways: (1) `tests/Feature/FlexibleAmountPlanTest.php` (5 tests) - proportionality (20x amount → 20x return), out-of-range rejection, and maturity crediting the exact snapshotted amount; (2) full suite still green (17 tests, 58 assertions) confirming zero regression on fixed-amount plans; (3) real headless-Chrome interaction test (`puppeteer-core`, same technique as entry 39) - simulated an actual slider drag, stepper clicks, and a duration switch against a live seeded "Premium Plan" (₹500-₹50,000, 10%/15% durations), confirmed every displayed number recalculated correctly at each step with zero browser console errors. Remembered this time to `npm run build` before testing (entry 39's lesson).

## 2026-07-19 (41) — Verified early-withdrawal is impossible; found and fixed a duration-return bug along the way

User asked to verify: investing in, e.g., the 6-month Growth Plan duration must make that return withdrawable only after 6 months, never earlier. Traced every path that can move money into a wallet or mark a holding withdrawn (`grep -rn "WalletBalance::credit"` / `"STATUS_WITHDRAWN"` across `app/`) - confirmed there are exactly three `WalletBalance::credit()` call sites (plan maturity, admin-approved deposits, referral commission) and plan-return crediting only ever happens in `MaturePlanHoldings`, gated by `UserPlan::scopeMatured()` (`matures_at <= now()`). `WithdrawRequestController::store()` only allows requesting up to the *current* wallet balance - since the return is never credited before maturity, it's structurally impossible to withdraw it early through any existing endpoint. Also noted `plans.early_close_allowed` (Phase 0/5) is a real column and admin checkbox but has no feature built against it yet - inert, not a bypass.

While building a regression test for this (`TrustBuilderGrowthPlanTest::test_six_month_plan_return_is_not_accessible_before_six_months_are_up`), found a real, separate bug: `UserPlan::currentHolding()` capped accrual at `$plan->total_return` (the Plan row's own base field) instead of the specific `plan_durations` row the user actually purchased. Growth Plan's base row holds the 3-month figure (₹513.76); a user who picked 6 or 12 months would accrue/get credited against that lower 3-month cap instead of their real promised return (₹535.92 / ₹588.82) - under-crediting at maturity and under-displaying on Portfolio for any non-default duration pick. Same bug duplicated in `UserPlan::chartPointsFor()` and `SendDailyReturnsEmail`'s inline cap calculation (entry 40).

Fixed all three call sites to prefer `$holding->planDuration?->total_return`, falling back to `$plan->total_return` only for legacy purchases with no duration row (the 5 original demo plans). `currentHolding()`'s returned array now also exposes `totalReturn` so `chartPointsFor()` doesn't have to re-derive it. Added `planDuration` to the eager-loads in `MaturePlanHoldings`, `SendDailyReturnsEmail`, and `UserPlan::holdingsFor()`. Full suite (12 tests, 41 assertions) green, including the new regression test that purchases a real 6-month holding, confirms the scheduler is a no-op and a withdrawal request is rejected while it's active, then fast-forwards `purchased_at`/`matures_at` together (not just `matures_at` alone - that under-counts elapsed days and was the first version of this test's own bug) and confirms the exact duration-specific return lands in the wallet.

**Known related gap, not fixed (out of scope for this check)**: legacy plans purchased with no `plan_duration_id` (i.e. `matures_at` stays null) are never picked up by `scopeMatured()`, so they have no automatic maturity/payout path at all - not an early-release risk, but their principal has no way to become withdrawable without manual intervention. Flagging for whenever those 5 original demo plans get real duration rows of their own.

## 2026-07-19 (40) — Daily "your investments grew today" email

New scheduled digest: `plans:send-daily-returns-email` (`app/Console/Commands/SendDailyReturnsEmail.php`, wired in `bootstrap/app.php`'s `->withSchedule()` at `dailyAt('09:00')`) emails every user a summary of how much each active holding accrued that day, one email per user (not per plan) listing every contributing plan plus the running total portfolio value.

- **Idempotency**: new `user_plans.last_daily_return_email_sent_at` (date, migration `2026_07_19_020000_...`) - a plain date-equality check, so a re-run (manual retry, missed `schedule:run`, etc.) on the same day can never double-send. New `UserPlan::scopeDueForDailyReturnEmail()` mirrors `scopeMatured()`'s shape: active, at least 1 full day old (day-0 purchases haven't accrued anything, matching `currentHolding()`'s `daysElapsed` math), not already stamped for today.
- **Fully-accrued holdings send nothing**: the command computes each holding's day-over-day increment (today's capped accrual minus yesterday's), the same capping logic as `currentHolding()`'s `min($dailyProfit * $daysElapsed, $maxProfit)` - a holding that already hit its `total_return` (e.g. Trust Builder past day 1) contributes ₹0 today and is excluded from that user's email, but is still stamped as processed so it stops being re-queried forever.
- **Mail**: `App\Mail\DailyReturnsMail` + `resources/views/emails/daily-returns.blade.php`, same `ShouldQueue` + `User::hasRealEmail()` guard as `DepositRequestReceivedMail` (entry 37) - phone/OTP accounts with the synthetic `@phone.gullakpe.local` placeholder are silently skipped, not queued against an undeliverable address.
- Verified live: seeded a real holding 3 days into a 1-year Growth Plan duration, ran the command, processed the queue (`MAIL_MAILER=log`), and confirmed the rendered HTML in `storage/logs/laravel.log` had the correct greeting/amount/per-plan row; a second same-day run queued zero emails. `tests/Feature/DailyReturnsEmailTest.php` (5 tests) codifies all of this: real-growth-left send, day-0-purchase skip, same-day dedup, phone-only-account skip, fully-accrued skip. Full suite (11 tests, 33 assertions) green.

## 2026-07-19 (39) — Explore page "gap from the start" - stale CSS build was masking the real fix

Follow-up to entry (38). The user reported a persistent left-edge alignment gap on `/explore` across several rounds of screenshots; each round of fixes (removing redundant `px-4`/`px-1` wrappers) looked right in the Blade source but the user kept seeing the same gap in their browser. Two real, separate bugs, both now fixed:

1. **The actual layout bug**: `.explore-layout` (`resources/css/app.css`) is a hand-written `display: flex; gap: 24px;` box - not a Tailwind utility. The empty `<form id="explore-compare-form">` added in the Trust Builder/Growth Plan work (entry 38, Explore's JS-free Compare flow) sat as its first flex child. Even though that form renders as a literal 0×0 box, `gap: 24px` still inserts a real 24px gap *before* the next flex item (`#step-plans-list`), pushing every card 24px right of the header/ticker above it. Moved the form to be a sibling *before* `.explore-layout` instead of a child inside it - `form="explore-compare-form"` (used by the compare checkboxes and the floating Compare button) works regardless of where the `<form>` tag lives in the DOM, so nothing else needed to change.

2. **Why earlier fixes in this same debugging session appeared to do nothing**: this repo's CSS is a Tailwind v4/Vite build (`public/build/assets/app-*.css`, hashed filename read from `public/build/manifest.json`), and this session had only ever run `php artisan serve` - never `npm run build` or `composer run dev`'s Vite watcher. Every new Tailwind utility class introduced across a whole afternoon of fixes (`-mx-4`, `px-1` removals, the labeled compare-pill, etc.) landed correctly in the Blade HTML but was **never compiled into the served CSS bundle** - confirmed by grepping the live bundle and finding `-mx-5` (the old, pre-fix class) still present and `-mx-4` completely absent. The browser was faithfully rendering genuinely stale styles the entire time. Ran `npm run build` to pick up everything; **any future CSS-visible change needs a rebuild (or `composer run dev`/Vite watch mode running) before it can be verified in a real browser** - `php artisan serve` alone is not sufficient for anything that touches Tailwind classes, only for pure Blade/PHP logic changes.

**How this was actually verified** (no Playwright MCP is connected in this environment): installed `puppeteer-core` (`npm install --no-save`, confirmed afterward that neither `package.json` nor `package-lock.json` picked up the entry, then removed the extra `node_modules/` packages it pulled in) driving the machine's existing Chrome install (`C:\Program Files\Google\Chrome\Application\chrome.exe`), and used `page.evaluate()` + `getBoundingClientRect()`/`getComputedStyle()` to get exact pixel positions of the header, ticker, and card elements at both a mobile (477px) and desktop (1345px) viewport - real measurements instead of eyeballing screenshots, which is what had been going wrong (misjudging sub-20px offsets by eye across multiple rounds). All key elements now report an identical content-start X coordinate (16px mobile / 264px desktop) after both fixes.

## 2026-07-19 (38) — Trust Builder Plan / Growth Plan unlock system + premium Plan page

Implemented `plans.md`'s full spec: a ₹199/1-day "Trust Builder Plan" that stays visible but locked until the user buys a ₹499 "Growth Plan" (3/6/12-month duration options, admin-configurable return per duration), plus the accompanying "premium fintech" plan-browsing rework (calculator, comparison, badges, filters/sort, bottom-sheet FAQs/terms, admin controls). Built entirely additive on top of the existing `Plan`/`UserPlan`/`WalletBalance` system - the 5 original demo plans are untouched.

**Schema** (all nullable/defaulted, zero data migration needed): new `plan_durations` table (generalizes "one plan, several durations, each with its own return" - a plan with no rows here behaves exactly as before); new `plans` columns (`plan_type`, `max_purchase_per_user`, `cooldown_days`, `requires_plan_id` self-referential, `unlock_enabled`, `unlock_message`, `marketing_badge`, `risk_level`, `max_slots`, `start_date`/`end_date`, `auto_mature`, `early_close_allowed`, `terms`, `faqs` json, `highlights` json); new `user_plans` columns (`plan_duration_id`, `duration_label`, `matures_at`) snapshotted at purchase time, same "never retroactively changes" principle as `invested_amount`. Seed migration creates the real Growth Plan (id 6) and Trust Builder Plan (id 7, `requires_plan_id` = 6).

**Purchase flow** (`PlanPurchaseController::purchase()`): new guards inserted before the wallet debit - schedule check, unlock check (`unlock_enabled` + `requires_plan_id`, flashes `open_unlock_popup`), purchase-limit check, cooldown check, duration resolution (`duration_id` request param, validated against the plan's own `plan_durations`). Insufficient-balance now also flashes `insufficient_balance` (needed/available) for the new common popup, and every successful purchase flashes `purchase_success` (plan snapshot) to drive the post-purchase popup. First scheduler in this codebase: `app/Console/Commands/MaturePlanHoldings.php` (`plans:mature-holdings`), wired via `bootstrap/app.php`'s new `->withSchedule()` - credits wallet + marks `withdrawn` for any active holding past `matures_at` on a plan with `auto_mature = true`. **No OS cron exists yet in any deployment of this app** - `schedule:run` needs a real cron entry added wherever this ships, or maturity never actually fires.

**Popups**: 4 new pure-CSS components (`unlock-required-popup`, `insufficient-balance-popup`, `purchase-success-popup`, `plan-badge`), following the exact peer/`:has()` convention `Explore::explore`'s search/filter panels already established (no JS) - new technique here is auto-opening from a session-flash-driven `checked` attribute (flash clears itself after one request, same as the existing toast). Included globally in `layouts/app.blade.php`.

**Plan Details / Explore**: duration-based "Estimated Calculator" + Portfolio Preview (radio group + `:has()`, same trick as `.pd-tabs` - precomputed per-duration blocks switched with zero JS since durations are a small fixed admin-set list), Progress Timeline, highlights/risk chips, extended `.pd-tabs` to 5 tabs (added FAQs - generic reusable `.faq-item:has(.faq-check:checked)` accordion CSS added to `app.css`, replacing the old dead `window.toggleFAQ()` - and Terms). Explore cards gained marketing badges, unlock badges, slots progress bars; `ExploreController` gained `sort` (5 options) and `risk_level` filter params plus a JS-free Compare flow (`explore-compare-form` id-linked checkboxes + `GET /explore/compare`, new `Explore::compare` table view).

**Admin**: `PlanManagementController`/`Admin::plans.form` extended (not replaced) with every new field plus a duration repeater (4 fixed row slots, radio-selected default, synced via id-matched upsert + delete-missing in `syncDurations()`). Verified end-to-end via real authenticated admin HTTP requests (create with 1 duration + full metadata, then update to 2 durations while pruning highlights - confirmed correct upsert/create/delete/default-flip behavior).

**i18n**: ~60 new literal-English-string keys added to both `public/lang/{en,hi}.json` (the ones actually fetched at runtime) and `resources/lang/{en,hi}.json` (kept in sync per DESIGN.md convention, though currently unused server-side) - the project's i18n engine works by exact runtime text-node matching against these dictionaries, no Blade-side markup needed.

**Testing gotcha worth remembering**: this repo's `APP_URL` includes a subpath (`/gullakpe/gullakpe-laravel`, for the dual XAMPP front-controller - see "Dual front controller" in this file's architecture section) that only the real `.htaccess` rewrites away. The PHPUnit HTTP test client has no web server involved, so any `actingAs()`-authenticated feature test hitting a real route 404s against the wrong path unless `APP_URL` is overridden for the `testing` environment. Fixed by adding `<env name="APP_URL" value="http://localhost"/>` to `phpunit.xml` - this was silently broken for any future authenticated feature test before now, not just the new ones added here. Also uncommented `RefreshDatabase` in the stock `tests/Feature/ExampleTest.php` stub (`GET /` depends on the `plans` table, which doesn't exist without migrations - was failing before this session, unrelated to this feature). New `tests/Feature/TrustBuilderGrowthPlanTest.php` (4 tests, 26 assertions) covers the full purchase/unlock/limit/insufficient-balance/maturity lifecycle; full suite is green.

## 2026-07-19 (37) — User-facing deposit confirmation email, alongside the admin one

Follow-up to entry (36): user asked for the same deposit-request email to also go to the *user*, not just the admin. Added `App\Mail\DepositRequestReceivedMail` (`ShouldQueue`, same database-queue reasoning as `NewDepositRequestMail`) rendering `resources/views/emails/deposit-request-received.blade.php` - a "Payment Received!" confirmation mirroring the in-app success-modal copy (amount/method/UTR/date, "under verification" pill, What's Next / Important callouts), dispatched from `DepositRequestController::store()` right after the admin email.

The real finding here: most users in this app **don't have a real email to send to**. Phone/OTP signup (the primary auth flow) fills the required `email` column with a synthetic, non-deliverable `"{phone}@phone.gullakpe.local"` placeholder (`PhoneAuthController.php:162`) - only Google-linked accounts (`GoogleAuthController.php:46`) have a genuine address. Added `User::hasRealEmail()` (`! str_ends_with($this->email, '@phone.gullakpe.local')`) and guarded the new mail dispatch on it, so phone-signup users silently get no email (correct - there's nowhere to send it) instead of a job queued against a fake address. Verified live: a phone-signup user submitting a deposit queued zero mail jobs; the same user with their email manually swapped to a real-looking address (simulating a Google-linked account) queued both the admin and user mail correctly, and both rendered without error via `queue:work` + a `storage/logs/laravel.log` inspection (still `MAIL_MAILER=log`, per entry (36) - real delivery is still waiting on the user's SMTP credentials).

## 2026-07-19 (36) — Admin email notification on new deposit requests

Submitting a deposit (UTR) request previously triggered **no notification at all** - not even the in-app `AdminNotification` withdrawal requests already get (`WithdrawRequestController` calls `AdminNotification::notify('withdrawal_request', ...)`; `DepositRequestController::store()` had no equivalent call). Added both:

- **In-app**: `AdminNotification::notify('deposit_request', 'New deposit request', '₹{amount} · {phone} · {method_label}')`, mirroring the withdrawal pattern exactly.
- **Email**: new `App\Mail\NewDepositRequestMail` (implements `ShouldQueue` - dispatched onto the app's existing `QUEUE_CONNECTION=database` queue via `Mail::to(...)->queue(...)`, not sent inline, so an unreachable mail server never delays the user's own request) rendering `resources/views/emails/deposit-request.blade.php` - a table-based, inline-styled HTML email (brand teal header, amount/phone/method/UTR/submitted-at summary card, "Review in Admin Panel" CTA linking to `admin.deposits`, footer) built for real email-client compatibility, not just browser rendering.

New `ADMIN_NOTIFICATION_EMAIL` env var (`.env`, `.env.example`) + `config('admin.notification_email')`. Left **empty on purpose** - user asked to build the whole system now and supply the real destination address and SMTP credentials later; the send is guarded (`if ($adminEmail = config(...))`), so an empty address means "skip silently," not an error. `MAIL_MAILER` also stays `log` (Laravel's default, unconfigured) until real SMTP credentials arrive - nothing about the code needs to change when they do, only `.env` values.

Verified live end-to-end: submitted a real deposit with the email setting empty (succeeded normally, no job queued, in-app notification created); then with a temporary test address set, confirmed a job actually landed in the `jobs` table, ran `php artisan queue:work --once` to process it, and inspected `storage/logs/laravel.log` (where the `log` mailer writes instead of sending) to confirm the rendered HTML had the correct amount/phone/method/UTR/date and a working admin-panel link - the exact same pipeline will deliver a real email the moment `MAIL_MAILER`/SMTP env vars point at a real provider. Test data and the temporary test email were removed/reverted afterward.

---

Dated, running log of what's actually been done in this project and why —
complements `git log` (which shows *what* changed) with the *reasoning* behind
it, and captures decisions/state that aren't visible from the diff alone. Add a
short dated entry after any non-trivial change. Newest entries at the top.

Related reference docs: [AGENTS.md](AGENTS.md) (how to work here) ·
[DESIGN.md](DESIGN.md) (visual/i18n reference) ·
[INSTRUCTIONS.md](INSTRUCTIONS.md) (setup/run) · [SECURITY.md](SECURITY.md)
(security checklist).

---

## 2026-07-18 (35) — Manual payment gateway: admin-managed, randomly-rotated UPI/Bank accounts

`/add-money` was hardcoded to a single UPI VPA (`DepositRequestController::UPI_VPA`) with no admin control and no bank-transfer option. Replaced with a real admin-controlled system:

- `AppSetting` gained three keys - `payment_mode` (`manual`/`online`), `manual_upi_enabled`, `manual_bank_enabled` - same key/value pattern as `referral_enabled`. "Online" is a mode placeholder only (no real gateway integration - no provider credentials exist yet); selecting it just shows a "coming soon" page instead of the form.
- Two new tables/models, `PaymentUpiAccount` and `PaymentBankAccount` (uuid route keys, `scopeActive()`, mirrors `Plan`'s shape), admin-managed via a new `PaymentGatewayController` (`app/Modules/Admin/Controllers/`) - full CRUD + toggle-active + delete, QR screenshots uploaded into `public/assets/payment-qr` (same no-storage-symlink convention as `PlanManagementController::storeUploadedImage()`). New sidebar entry "Payment gateway".
- `DepositRequestController::create()` re-queries `PaymentUpiAccount::active()->inRandomOrder()->first()` (or the bank equivalent) **fresh on every request** - this alone is what makes the shown account "random every time the user opens the page," no caching or session pinning involved. If both manual methods are enabled the user gets plain server-rendered `?method=upi`/`?method=bank` tabs; if only one is on, that one shows directly; if nothing's configured, a graceful unavailable notice renders instead of a broken form.
- Deliberately did **not** add a FK from `deposit_requests` to the new account tables (would've meant altering an existing table with a partial unique index on it) - instead a `pay_to_label` hidden field, filled in server-side from whichever account was actually shown, gets folded into `method_label` as a plain-text snapshot (e.g. `"UPI · merigullak@upi"`), same fidelity as the old `"Google Pay"`-style label.
- QR is the admin's static uploaded screenshot, not a dynamically generated one - confirmed with the user that no amount-encoding QR generation was wanted, consistent with the existing manual-UTR-verification flow (user pays externally, submits a reference number, admin verifies).
- UTR validation now branches by method: UPI keeps the existing `digits:12` shape, but bank transfer references (NEFT/RTGS UTRs, IMPS ref numbers) aren't reliably 12 digits, so that path is `string|min:4|max:30` instead.
- Added ~40 new dictionary entries to `public/lang/{en,hi}.json` (mirrored to `resources/lang/`) for every new string on the rebuilt `Deposits::create` page and the new `Deposits::payment-unavailable` view - admin views stay untranslated per the existing convention (`layouts/admin.blade.php` never loads `<x-i18n-engine/>`).

**Same day, follow-up correction #1**: initial version let UPI and Bank Transfer be independently toggled on/off (both could be active at once, with frontend tabs to switch between them) - user corrected this immediately: exactly one manual method may be active at a time, same shape as `payment_mode` itself. Replaced the two `manual_upi_enabled`/`manual_bank_enabled` booleans with a single `manual_method` (`upi`/`bank`) `AppSetting` key; removed the frontend `?method=` tabs entirely (`DepositRequestController::create()` just reads the one active method); `store()` only accepts that one method regardless of what's posted. Also hid the manual method selector + both UPI/Bank account-list sections on the admin page entirely whenever Online mode is selected (previously always visible regardless of mode) - only the Online/Manual radio picker itself stays visible so the mode can still be switched.

**Same day, follow-up correction #2**: user then pointed out the admin page still had two separate decisions stacked on top of each other - "Collection mode" (Online vs Manual) at the top, then a second nested "Active manual method" (UPI vs Bank) radio underneath it, only shown when Manual was picked. That's a hierarchy where the user wanted one flat choice. Collapsed `payment_mode` (`online`/`manual`) + `manual_method` (`upi`/`bank`) into a **single** `AppSetting` key `payment_mode` with three possible values - `online` | `upi` | `bank` - removed `manual_method` entirely. Admin page is now one radio group with three cards (UPI / Bank Transfer / Online Payment Gateway); the UPI accounts list, Bank accounts list, and "hidden while Online is active" note are mutually exclusive `@if`/`@elseif`/`@else` branches keyed directly off that one value, not a mode-check plus a sub-method-check. `DepositRequestController` reads the same single value on both `create()` and `store()`.

**Same day, follow-up correction #3**: user said the Online Payment Gateway placeholder isn't needed at all (no real gateway exists to eventually wire up to, and it was cluttering the choice) - dropped it entirely rather than keeping it as a third option. `payment_mode` is now just `upi` | `bank` (validation in `PaymentGatewayController::updateSettings` tightened to `in:upi,bank`); Collection mode is a 2-card radio; the admin account-list section is a plain `@if(upi) ... @else (bank) ...` with no more "hidden while Online" branch to fall through to. `DepositRequestController::create()`/`store()` dropped their `mode === 'online'` branches. `Deposits::payment-unavailable.blade.php` stays - it's still used for the "no active accounts configured" fallback, just no longer for an online placeholder. Removed the now-unreachable "Online payments coming soon" / "...not connected yet..." dictionary entries from all four `lang/{en,hi}.json` files.

**Same day, follow-up correction #4**: user pointed out `/add-money` still asked for "Your registered phone number" even though the user is already signed in - the field was inherited unchanged from the original hardcoded-UPI version of this page (`WithdrawRequestController`/`Withdrawals::create.blade.php` has the identical field/label, left untouched - not in scope of this ask). Removed the phone `<input>` entirely; `DepositRequestController::create()`/`store()` now require `$request->user()` (redirecting to `login` if absent - depositing has to credit a specific wallet, unlike Home which supports guest browsing) and pull `$user->phone` server-side. Incidental security fix: `store()` previously trusted a client-posted `phone` field with no ownership check at all, so anyone could type an arbitrary phone number and credit a deposit to someone else's wallet - it's now always the authenticated session's own phone, never client input. `Withdrawals` has the same pre-existing gap and was deliberately left alone since it wasn't what was asked.

**Same day, follow-up correction #5**: same complaint again, this time about the Amount field - Home's "Quick Add Amount" card already collects the amount and POSTs it to `deposits.start`, which flashes it to the session before redirecting to `/add-money`; the page then showed it *again* as an editable input. Replaced the editable `<input type="number">` with a read-only `₹{amount}` display + a "Change" link back to Home, plus a hidden `<input name="amount">` carrying the value through to `store()`. The real fix is in `create()`: it now resolves the amount via `old('amount', session('deposit_amount_prefill'))` (so a failed UTR submission still round-trips the right amount through `back()->withInput()`) and, if neither is present/valid (e.g. a stale or direct link to `/add-money` with no amount ever chosen), redirects to `home` with an error instead of ever rendering a payment page with no amount - this also made the old "amount mirror" JS (`#amount` input → `#qr-amount-display`) dead code, removed along with the input itself since the figure can no longer change client-side.

**Same day, follow-up correction #6**: the read-only "Amount to add ₹X + Change" card from #5 was itself redundant - the payment card just below it already shows "Amount to Pay ₹X". Removed that top card and its "Change" link entirely; the amount now displays exactly once, inside the UPI/Bank payment card, matching the reference screenshot. The hidden `<input name="amount">` stays (still needed for `store()`), it's just not paired with any visible card anymore.

**Same day, follow-up correction #7**: `Deposits::create.blade.php` and `Deposits::payment-unavailable.blade.php` each had their own inline "← Back to GullakPe" link sitting inside the page content, below the layout's own sticky header - `layouts/simple.blade.php` already supports a proper header back arrow via `@section('backRoute', ...)` (the mechanism the Legal pages - `terms`/`privacy`/`faq` - already use), it just wasn't wired up here. Added `@section('backRoute', route('home'))` to both views and deleted the redundant inline links; `payment-unavailable.blade.php` keeps its separate "Back to Home" CTA button, which serves a different purpose (the primary action on an otherwise-empty page) and isn't just navigation-back.

**Same day, follow-up correction #8**: restyled the "256-bit Encryption / UPI Protected / Secure Transactions" trust-badge row (both the UPI and Bank Transfer cards have their own copy) - dropped the green `bg-emerald-50 border-emerald-100` box entirely per request, swapped FontAwesome (`fa-solid fa-lock`/`fa-shield-check`/`fa-circle-check`) for Bootstrap Icons (`bi-lock-fill`/`bi-shield-fill-check`/`bi-check-circle-fill` - confirmed these exact names exist in `public/assets/bootstrap-icons-list.json`), and recolored everything to grey (`text-slate-400`). For "a little section attractive" without reintroducing a colored box: a plain `border-t border-slate-100` divider instead of a bg/border card, each icon in a small `bg-slate-100` circle chip, and thin `w-px bg-slate-100` vertical dividers between the three items. Confirmed Bootstrap Icons is actually available on this page - `resources/css/app.css` has `@import "bootstrap-icons/font/bootstrap-icons.css"`, bundled into the one global stylesheet every layout (including `layouts.simple`) loads via `@vite`, not a separate CDN link some pages might be missing.

**Same day, follow-up correction #9**: restyled the countdown banner (both UPI and Bank Transfer cards) to match a reference screenshot - was a light `bg-amber-50` bar flush with the card's top edge (`border-b`, FontAwesome clock, amber text). Replaced with an inset `rounded-full` pill (`bg-[#0A5C66]`, the app's own brand teal) with margin around it instead of spanning edge-to-edge: `bi-clock` (Bootstrap Icons, matching the prior correction's icon-set switch) + "Complete your payment within" in white on the left, the countdown number in bold gold (`text-amber-400`) + "min" in white on the right. The `id="payment-countdown"` span (the one the countdown `setInterval` in the `<script>` block targets) stays exactly where the live-updating JS expects it - only the surrounding markup/styling changed, not the JS.

**Same day, follow-up correction #9b**: the inset rounded-pill treatment from #9 didn't read as matching the reference and felt off - user said the *structure* of the original flush bar was fine, it just needed the right colors/polish, not a redesign into a separate floating chip. Reverted to a full-width bar flush with the card's top edge (no `mx-`/`mt-`/`rounded-full` - relies on the parent `.premium-card`'s own `overflow-hidden` + `border-radius: 24px` to clip the top corners automatically, same mechanism the original amber bar used), but recolored using the app's own established brand gradient (`bg-gradient-to-br from-[#0A5C66] via-[#0A5C66] to-[#04242F]` - the exact gradient Home's hero card and primary "Add Money" CTA already use) instead of a flat color, for a more cohesive/premium look tied to the rest of the app rather than a one-off color choice.

**Same day, follow-up correction #9c**: user reported the gold clock-icon/countdown color "wasn't showing" - the color itself (`text-amber-400`) was already correct in the Blade source, but `public/build/assets/app-*.css` hadn't been rebuilt since before this whole payment-gateway session's edits (`npm run build` was never run - only `php artisan view:clear` after each Blade change, which clears *compiled views*, not the Vite-built CSS bundle). Tailwind v4's `@source`-scanned classes only exist in the compiled stylesheet if they were present the last time `npm run build`/`vite build` ran, so every new utility class introduced across this session's edits (`text-amber-400`, the `bg-gradient-to-br` trio, etc.) was silently missing from what the browser actually loaded until now. Ran `npm run build`; confirmed `.text-amber-400{color:...}` is present in the new hashed CSS file and the page now references that new hash. **Lesson for next time**: after any Tailwind class change in this project, `npm run build` (not just `view:clear`) is required before the change is visibly real in a browser - `view:clear` alone will make server-rendered HTML markup correct while the CSS silently lags behind, which is exactly the gap that happened here.

**Same day, addition #11**: extended the "Pay using any UPI App" icon row into real, functional UPI-intent deep links with the amount pre-filled - previously just static `<img>` tags with zero click behavior. Each icon is now an `<a>` to a `upi://pay?pa=...&pn=...&am={{ $amount }}&cu=INR&tn=GullakPe+Deposit` intent (the NPCI-standard scheme every compliant UPI app registers a handler for); PhonePe/Google Pay/Paytm additionally use their own well-documented custom schemes (`phonepe://pay?`, `tez://upi/pay?`, `paytmmp://pay?`) to try opening that exact app rather than the OS chooser. Added 5 more apps to the row (Amazon Pay, MobiKwik, iMobile Pay, Airtel Payment Bank, HDFC Bank) per user request - confirmed with the user first that (a) I don't have real logo files for these five and they'll supply them, and (b) guessing custom URI schemes for apps I can't verify is worse than the universal `upi://` fallback (a wrong guessed scheme silently does nothing on a real phone). Each `$upiApps` entry references an expected filename (`public/assets/amazonpay.png`, `mobikwik.png`, `imobilepay.png`, `airtelpaymentsbank.png`, `hdfcbank.png`) that doesn't exist yet - the view checks `file_exists()` per icon and falls back to a plain initials badge (matching the existing "Any UPI App" grey-circle style) so the row never shows a broken image; dropping a correctly-named file into `public/assets/` is the only step needed to swap a placeholder for the real logo, no code change required.

**Same day, follow-up correction #11b**: user rejected the initial layout - `rounded-full` circular icons crammed into one `flex flex-wrap` row. Rebuilt as a `grid grid-cols-4` of square (`rounded-[14px]` card, `rounded-[10px]` icon box - matching this page's other existing radius values, not circles) tiles with the app name as a visible label under each icon, not just a hover title - 9 tiles (8 apps + "Any UPI App") settle into 3 clean rows instead of an uneven wrapped line. Removed iMobile Pay from `$upiApps` right after per a follow-up ask - 8 tiles (7 apps + "Any UPI App") now fill the 4-column grid evenly into 2 full rows.

**Same day, follow-up correction #11c**: moved the "256-bit Encryption / UPI Protected (or Bank-Grade Security) / Secure Transactions" trust badges out of the payment card (where they sat right after Download QR/Share buttons) to the true bottom of the page - below the Submit button and the "Deposits are verified manually..." note, as the last thing before `</form>`. This also deduplicated it from two near-identical copies (one per UPI/Bank card) into one shared block, with only the middle label staying conditional (`{{ $activeMethod === 'upi' ? 'UPI Protected' : 'Bank-Grade Security' }}`).

**Same day, follow-up correction #11d**: dropped the visible text labels under each trust-badge icon per request ("just put the icons") - the three `<span>` captions are gone, replaced by `title`/`aria-label` on each circle (still reachable by screen readers and still translated via the i18n engine's `TRANSLATABLE_ATTRS`, just not rendered as visible page text). Icons grew from `w-8 h-8`/`text-[13px]` to `w-12 h-12`/`text-[19px]`, and each circle picked up a soft `shadow-sm shadow-slate-200/60` for a bit of depth/polish now that they're the only content in the row.

**Same day, addition #12 (holistic design pass, via the `impeccable` skill)**: after ~10 rounds of piecemeal element-by-element tweaks, ran a full audit of `Deposits::create.blade.php` against product-register design rules instead of another isolated fix. Findings and fixes:
- **Icon system was split two ways** - FontAwesome (`fa-solid`/`fa-regular`: copy/download/share/bank/shield icons) alongside Bootstrap Icons (everything added in later rounds). Swapped every remaining FA icon on this page to its BI equivalent (`bi-copy`, `bi-download`, `bi-share-fill`, `bi-bank2`, `bi-shield-check`, `bi-check-lg` for the JS-injected "Copied" state) - confirmed each name exists in `public/assets/bootstrap-icons-list.json` before using it. (The one FA icon still on the rendered page, the header back-arrow, lives in `layouts/simple.blade.php` - shared across many pages, out of scope for a page-specific pass.)
- **Text contrast**: `text-slate-400` (~2.8:1 against white) was used for real reading content - row labels, helper captions, descriptions - well under WCAG's 4.5:1 floor for body-sized text. Bumped every real-text instance to `text-slate-500` (~4.6:1). Left `text-slate-400` on the four purely-decorative trust-badge/note icons alone (settled in an earlier round, and icons aren't held to the same text-contrast bar).
- **Shimmer overload**: `.btn-shimmer-cta` (a periodic shine meant for "primary CTAs" per its own doc comment) had been applied to both the countdown banner *and* the submit button - two things visibly shimmering dilutes what the motion is supposed to signal. Removed it from the countdown banner; it's now exclusive to Submit, the one true primary action.
- **Missing focus states**: copy buttons, download/share buttons, the UPI-app grid tiles, the submit button, the How-It-Works FAB, and its modal's close button had hover/active states but no visible focus ring - a real gap for keyboard users. Added `focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40` (or `/40` → full ring + offset on the two highest-emphasis buttons) throughout, matching the ring color the UTR input's own pre-existing focus style already uses.
- **No `prefers-reduced-motion` handling anywhere on the page** - the FAB's perpetual bob and the How-It-Works modal's entrance choreography are both purely decorative. Added a `@media (prefers-reduced-motion: reduce)` block in the page's own `<style>` tag that kills the bob and swaps the modal/step entrance animations for an instant, fully-visible state.
- Did **not** attempt a full typography-scale renumbering (the page has ~16 distinct one-off `text-[Npx]` sizes where a tighter product-register scale would use ~7) - flagged as a real finding, but treated as higher-risk/lower-certainty without a way to visually screenshot-verify the result in this environment, so left for a deliberate follow-up rather than bundled into this pass.
- All changes verified via real HTTP requests through the full signup → deposit flow, in both UPI and Bank Transfer modes (`npm run build` re-run per the established lesson; several new arbitrary-value utility classes did require it this time).

**Same day, addition #13**: added the same `data-copy` button pattern to the Bank Transfer card's two rows that were missing it (Account Holder, and the combined Bank name/Branch row) - previously only Account Number and IFSC Code were copyable, an inconsistency the other two rows had no good reason to keep.

**Same day, addition #14**: user called the trust-badge row "very casual" and asked for it to look more attractive. It had been a bare `border-t` hairline with flat grey circles floating below - no real container, nothing distinguishing it as a deliberate module rather than an afterthought. Gave it one: a soft `rounded-[20px]` card (subtle `from-slate-50 to-white` gradient, hairline border) instead of just a top divider; each icon moved from flat `bg-slate-100` to a white circle with a soft `ring-1 ring-slate-200/80` + layered shadow for real depth, plus a small hover lift (`hover:-translate-y-0.5` + shadow growth) since this is a footer element where one subtle delight touch is fine; the hard 1px dividers became gradient-fade lines (`from-transparent via-slate-200 to-transparent`) instead of flat hairlines touching top and bottom. Kept the icon-only content and grey icon color exactly as settled in corrections #11d/before - this pass was purely about the container/material quality, not re-litigating what's shown.

**Same day, follow-up correction #14b**: immediately after, user asked for the labels back too, "in little size" - reversing #11d's icon-only decision now that the surrounding chrome earned its keep. Added a `text-[9px]` caption under each icon (`max-w-[68px]` so the longer labels wrap onto two lines predictably instead of overflowing or needing a manual `<br>` that would break at the wrong point once translated to Hindi). The `title`/`aria-label` attributes from #11d stayed in place - harmless now that visible text also exists, and no dictionary changes were needed since "256-bit Encryption"/"UPI Protected"/"Bank-Grade Security"/"Secure Transactions" were already real keys in all four `lang/{en,hi}.json` files from when they were first visible text, before #11d moved them to attribute-only.

**Same day, addition #16**: two small follow-ups after #15 - shrank the "Verification in" caption inside the success screen's progress ring from `text-[8px]` to `text-[6.5px]` per request ("min" left as-is, same size, not mentioned). Separately, trimmed the "Pay using any UPI App" grid on `Deposits::create.blade.php` back down to just PhonePe/Google Pay/Paytm - removed the Amazon Pay/MobiKwik/Airtel Payment Bank/HDFC Bank entries (and their deep-link scheme/logo-fallback config) from the `$upiApps` array added in addition #11/correction #11b, per "just keep the phone pay pay TM and Google Pay and remove the others along with the triggering mechanism". Left the generic "Any UPI App" catch-all tile in place (universal `upi://pay` intent, not one of the named apps asked to be removed) - the grid is now a clean single row of 4 tiles instead of 7+1 wrapping to two uneven rows.

**Same day, addition #15**: replaced the plain "your deposit is under review" flash-message redirect after a successful submission with a full "Payment Received!" confirmation screen, built pixel-for-pixel from a reference screenshot the user provided (plus a pre-made icon file they dropped at `public/assets/submit_banner.jpeg` for the top banner - used directly, no CSS-built decoration needed since the circular tint/dots were already baked into the image). New view `Deposits::success.blade.php`: top banner + "Payment Received!" + status pill, a card with an SVG ring ("Verification in 5-10 min" - `stroke-dasharray`/`stroke-dashoffset` on a plain `<circle>`, no chart library) beside a 3-step dashed-line tracker (Details Received ✓ → Under Verification → Amount Will be Added), a Payment Summary grid (amount/method/UTR-with-copy/date), "What's Next?" (blue) and "Important" (amber) callouts, a notification note, a "Go to Dashboard" CTA, and a 3-icon trust-badge footer - all in Bootstrap Icons, matching the rest of this module's icon system.

The real implementation problem wasn't the markup, it was *getting there*: `store()` now flashes structured data (`depositSuccess` - amount/methodLabel/utr/submittedAt) instead of a plain string, and `create()` checks `session()->has('depositSuccess')` **before** the amount guard added in correction #5 - by the time the post-submit redirect lands back on `/add-money`, the original amount-prefill flash is long consumed, so without this ordering the guard would fire first and bounce the user to Home before the success view ever got a chance to render. Verified live end-to-end: real signup → real deposit submission → confirmation screen renders with the exact submitted amount/UTR/date, the `deposit_requests` row is unaffected, and reloading `/add-money` afterward (flash now consumed) correctly falls back to the normal amount-guard redirect instead of showing a stale success screen.

**Same day, addition #10**: added a "How It Works" floating-button + popup to `Deposits::create.blade.php`, modeled on a reference screenshot (numbered teal-circle steps under a book-icon header). A `.how-it-works-fab` button (Bootstrap Icons `bi-question-lg`, gentle up/down bob via a new page-scoped `<style>` keyframe - distinct from `.btn-shimmer-cta`'s shine so it reads as "tap for help" not "primary action") sits fixed bottom-right; clicking it opens a modal built on the exact same open/close/click-outside/Escape JS pattern as the icon-picker modal in `Admin::plans.form.blade.php`. Step content is method-aware (`$howItWorksSteps` branches on `$activeMethod` - UPI steps mention scanning the QR and a UPI app; Bank steps mention copying account/IFSC and NEFT/IMPS/RTGS), each rendered with a staggered `animation-delay` reusing the already-global `fadeInUp` keyframe so they cascade in one at a time when the modal opens - no JS-driven stagger needed, just CSS + the existing show/hide toggle. Added ~19 new dictionary entries (including `aria-label="How it works"`/`"Close"`, which the i18n engine also translates via its `TRANSLATABLE_ATTRS` list) to all four `lang/{en,hi}.json` files. Ran `npm run build` afterward per the earlier stale-CSS lesson - this time the hash *did* change (new arbitrary-value utilities like `max-w-[380px]` need `@source` scanning to pick them up), confirming the rebuild step was actually necessary here, not just precautionary.

**Same day, follow-up correction #9d**: added the app's existing `.btn-shimmer-cta` class (`resources/css/app.css` - a periodic shine-sweep already used on Home's primary "Add Money" CTA, chosen over the hover-only `.shimmer-effect` since neither the countdown banner nor a form submit button gets meaningfully hovered on mobile) to both the countdown banner and the "Submit for Verification" button, for the same premium/attention-drawing motion Home's own CTA already has. Ran `npm run build` again per the previous entry's lesson - came out byte-identical (same output hash `app-DxM0nwja.css`), confirming `.btn-shimmer-cta` was hand-written CSS already compiled in (not a `@source`-scanned Tailwind utility), so this particular change didn't actually need a rebuild - but checking is now the default instead of assuming.

---

## 2026-07-18 (34) — Profile's "Language & Region" row now actually toggles EN/HI

Same disabled-placeholder pattern as entry (33)'s Privacy/Terms/FAQ rows -
"Language & Region" sat in the same `opacity-60` dead `@foreach` array with
no handler. Pulled it out into a real button
(`onclick="window.toggleLanguage && window.toggleLanguage()"`, the same
global the header translate icon already calls) with a
`<span data-current-lang>EN</span>` indicator that the shared i18n engine
(`components/i18n-engine.blade.php`) already updates for free via its
`updateIndicators()` sweep - no new JS needed, just wiring into the
existing mechanism.

Caught the same class of gap as (33): the label itself, "Language &
Region", had no dictionary entry and stayed in English after everything
else on the page translated. Added it to `en.json`/`hi.json` (mirrored to
`public/lang/`), rebuilt, and re-verified end-to-end via Playwright (real
login as the demo referrer, navigate to Profile, click the row, confirm
the label renders "भाषा और क्षेत्र" and the indicator flips to "हि").
Lesson holding steady across both entries: always toggle to Hindi and
re-check the *specific new string*, not just the page generally - generic
"does the page still translate" checks keep missing the row you just
added.

---

## 2026-07-18 (33) — Real FAQ page, plus wiring Profile's Privacy/Terms/FAQ links to the pages entry (32)/Legal module already built

Profile's "Support & Others" list had "Privacy & Data" and "Frequently
Asked Questions" as disabled `opacity-60` "Soon" placeholders - dead ends,
same pattern as everything else in that list. Now that real `/privacy`
and `/terms` pages exist (`Legal` module), wired both up as real links and
added a third: `LegalController::faq()` / `Legal::faq` (new `/faq` route),
reusing the same accordion pattern already proven on the Rewards page
(`data-faq-item`/`data-faq-toggle`/`data-faq-body`, real vanilla-JS
toggle, not a dead handler).

FAQ content is broader than the Rewards page's referral-only FAQ -
grouped into Account & Security, Deposits & Withdrawals, Investments,
Refer & Earn (2 items reused verbatim from Rewards' existing FAQ so the
translation keys aren't duplicated), and Language & Support. Its back
button goes to `route('profile')` specifically (not `route('home')` like
Terms/Privacy) since Profile is the only place it's reached from.

Caught one real gap before calling it done: the "Investments" group
heading had no dictionary entry and silently stayed in English after
translating to Hindi - everything else translated correctly, this one
slipped through the first pass. Added it and re-verified the whole page
translates cleanly.

---

## 2026-07-18 (32) — Referral codes are now encrypted in the link, never shown or copyable raw

User flagged a real gap: the Rewards page showed the plain referral code
("GULDEMO1") next to its own copy button, separate from the link. That
contradicts the actual business rule - registration is only supposed to
happen through the shared link itself, so a human-readable, separately
copyable code undermines that (anyone could hand out just the code, or
manually build a `?ref=CODE` URL, bypassing whatever the "official" link
was meant to be).

Fix: `RewardsController` now does `Crypt::encryptString($user->referralCode())`
before putting it in the link's `?ref=` param, instead of the plain code -
the value in the URL is now an opaque ciphertext blob, not a readable
code. `HomeController::captureReferralCode()` decrypts it back
(`Crypt::decryptString()`, catching `DecryptException` for anything
tampered/garbage/old-style plain codes - fails silently, no attribution,
no crash) using this app's own `APP_KEY`, so nobody can read or forge a
valid token without it. Removed the standalone "Your Code" chip and its
dedicated copy button from `Rewards::rewards` entirely - the link is now
the only shareable thing on the page, with a line making that explicit
("Only friends who sign up through this link count as your referral.").

Verified end-to-end: the displayed link no longer contains the raw code
string anywhere; the *old* plain `?ref=GULDEMO1` style link no longer
attributes a referral at all (confirmed via a real signup - `referred_by`
stayed null); a tampered/garbage token doesn't 500, just no-ops; and a
fresh signup through the real, current encrypted link still correctly
sets `referred_by` and (on first investment) still credits the right
commission - the underlying feature from entries (30)/(31) is unchanged,
only how the code travels changed.

---

## 2026-07-18 (31) — Cleared ad-hoc test users, added ReferralDemoSeeder

The local dev DB had accumulated 8 users from manual testing across this
whole session (Refer & Earn's own verification plus earlier MPIN/login
redesign testing) - test phone numbers, throwaway names ("Test Signup
User", "Final Test", "Unattributed Chris"), and matching orphaned rows in
`user_plans`, `wallet_balances`, `deposit_requests`, `withdraw_requests`,
`phone_otps`, `admin_notifications`. None of it was real data (this is a
local XAMPP dev environment), so deleted all of it outright rather than
picking through row by row - `plans`/`plan_categories` (catalog, from
`PlanSeeder`) and `app_settings` (admin config) were untouched.

Replaced it with `database/seeders/ReferralDemoSeeder.php` - two
clearly-named, reproducible accounts (`Demo Referrer` /9000000001,
`Demo Friend` /9000000002, both MPIN `1234`) with a real referral link
between them, a real plan purchase, and the resulting commission already
credited - so `/rewards` has meaningful data to look at without manually
driving a signup through the UI every time. Idempotent like `PlanSeeder`
(`updateOrCreate`/`firstOrCreate` throughout) - safe to re-run, verified
running it three times in a row doesn't create duplicate rows or
double-credit the wallet. Not wired into `DatabaseSeeder::run()`, matching
`PlanSeeder`'s existing standalone convention (`php artisan tinker
--execute="(new Database\Seeders\ReferralDemoSeeder())->run();"` or the
class name directly with `db:seed --class=`).

---

## 2026-07-18 (30) — Refer & Earn: real referral tracking + commission, built on top of entry (29)'s settings

`/rewards` was 100% dead until now: the controller passed no data, every
stat/balance/referral-code/QR-code in the view was hardcoded (the code was
literally the string `GULLAK8820` for every visitor), and every button
called a `window.*` function that doesn't exist anywhere in the app -
leftover from the pre-Laravel static prototype. Built the real thing,
reusing entry (29)'s already-working `AppSetting` (`referral_enabled`,
`commission_percent`) rather than adding new admin-side config.

**Schema**: `users` gets `referral_code` (nullable, unique, generated
lazily via `User::referralCode()` on first use - no backfill needed) and
`referred_by` (nullable FK to `users.id`). New `referral_commissions` table
is the ledger (`referrer_id`, `referred_user_id`, `user_plan_id` **unique**
- the real guard against double-crediting - `amount`, `commission_percent`
snapshotted at credit time since the admin rate can change later).

**Flow**: `HomeController::index()` captures `?ref=CODE` into
`session('pending_referral_code')` (same session-flash pattern
`deposits.start` already uses) when the code resolves to a real user and
the program is enabled. Both real signup paths -
`PhoneAuthController::setMpin()` and `GoogleAuthController::callback()` -
attach `referred_by` from that session value at account-creation time only,
never overwritten after. `PlanPurchaseController::purchase()` checks, after
a successful `UserPlan::create()`: was this the referred user's *first*
plan purchase ever, is the program enabled, does the referrer still have a
phone? If so, credits `commission_percent`% of the invested amount straight
to the referrer's wallet via the existing `WalletBalance::credit()` -
same mechanism deposit-approval already uses, no new wallet code needed.
One-time only, decided with the user up front (not a flat cashback, not an
ongoing/recurring commission on future investments - that's a bigger
feature for later if wanted).

Rebuilt `Rewards::rewards` from scratch (real link/code, copy button,
WhatsApp share, invite/invested stats, referral history with masked
phones matching the MPIN screens' convention). Verified end-to-end with
two real signups through a real referral link, a real plan purchase, and
confirmed the exact expected commission (₹199 plan × 5% = ₹9.95) landed
in the referrer's wallet and displays correctly in both English and
Hindi. Also confirmed toggling `referral_enabled` off actually blocks new
attribution, proving entry (29)'s admin toggle now controls something real.

**Follow-up same day**: first pass dropped the old page's QR code section
and Commission Center card entirely, which turned out to be the wrong
call - user wanted the original visual richness kept, just made real
instead of removed. Restored both: QR code is a genuine scannable image
(`api.qrserver.com`, same external-image-API pattern this app already
uses for flags/avatars - no new dependency) with a working download link;
Commission Center keeps its card styling but reports real numbers (Total
Earned, current Wallet Balance, Referral count) rather than the old
Claimed/Pending/Available breakdown, since - confirmed with the user -
commissions credit instantly with no claim step, so there's never
anything actually pending. Also restored a Referral Statistics grid
(Total Invites / Registered / Deposited / Invested, the last two computed
for real from `DepositRequest` and `UserPlan`) and a working FAQ
accordion (real vanilla-JS toggle, not the dead `window.toggleRewardsFAQ`
the original had).

---

## 2026-07-16 (29) — Real settings entry for the referral toggle/config (the thing entry (9) flagged as follow-up work)

User reported the app's MVC structure felt "mismatched" - business config
living in frontend JS instead of the Laravel backend. Traced it to a
specific, previously-flagged gap: entry (9) removed the old Admin Settings
debug panel and explicitly noted that `gullakpe_admin_referral_enabled`
etc. were left as permanently-dead localStorage flags, and that "if a real
referral-program on/off switch is ever wanted, it needs a real settings
entry now, not this leftover flag." That real settings entry didn't exist
until now - the Ops Console's "Referral program" panel (still fully
present, entry (11)/(18)) was writing `referral_enabled`,
`commission_percent`, `cashback_amount`, `settlement_time` to the admin's
own browser's localStorage, and the mobile app's `app-state.js`/
`navigation.js` read those same key names from *their* browser's
localStorage - two different origins that can never share state, so the
toggle/config could never actually reach a real user. Structurally the
same bug Deposits (entry 26) had before it got a real backend.

Added: `app_settings` table (simple key/value) + `App\Models\AppSetting`
(`get()`/`set()`/`many()`/`current()`, defaults matching what the JS used
to fall back to). `AdminController::updateSettings()` and
`toggleReferral()` (validated, logged to the `admin_security` channel like
every other admin action) replace the JS handlers entirely - the two forms
in `Admin::dashboard` are now plain `<form method="POST">`s, no JS driving
them. Added a `max_deposit_limit` field to that form - it was one of the
five dead flags but never had *any* admin UI, not even the removed one.
`AppServiceProvider` shares real settings with `layouts.app` via a View
composer (`window.__appSettings`), which `app-state.js` (11 call sites)
and `navigation.js` (1 call site) now read instead of localStorage.

Two things worth flagging if this area is touched again:
- **Wallet adjustment, Simulations, and Activity logs on the same Ops
  Console page are still localStorage-only demo tooling** - deliberately
  left alone, this pass was scoped to the *config* that gates real user
  behavior (referral on/off, rates, limits), not the demo/testing tools.
  The Simulate buttons still need cashback/commission numbers though, so
  they now read `window.__opsSettings` (rendered straight from
  `AppSetting::current()` in `Admin::dashboard`, not localStorage) instead
  of drifting from whatever's actually configured.
- **The settlement-time field's old placeholder ("e.g. T+2 days") never
  matched what `checkDailyRelease()` in app-state.js actually parses it
  as** (`"HH:MM"`, split on `:`). Changed the placeholder to "e.g. 00:00"
  to match reality - a real pre-existing inconsistency, not something this
  pass introduced, but worth knowing the field's semantics were always
  "time of day", never "T+N days".

Since the settings form now does a real page POST instead of an
in-place JS update, added a small `#settings`-hash panel restore in
admin.js so saving doesn't dump the admin back on the Wallet panel.

Verified over real HTTP (`php artisan serve`, not Tinker - entry (28)'s
`$errors`-undefined note holds again here): logged in, fetched the
dashboard and confirmed the settings form/toggle render server-side
values; POSTed real settings changes and confirmed they persisted across
a reload; POSTed invalid values (negative amount, 150%, non-numeric limit)
and confirmed validation rejected them *and* the database still held the
last-good values (didn't just fail cosmetically); toggled the referral
switch off and back on and confirmed `window.__appSettings.referralEnabled`
on the real Home page reflected it both times; confirmed Explore,
Portfolio, Rewards, Profile, and the admin Deposits page all still return
200. `npm run build` succeeded (new `admin-*.js`/`app-*.js` hashes), ran
`php artisan view:clear` per the project's known stale-view-cache gotcha,
then re-verified over HTTP against the freshly built assets. Reset the
settings used during testing back to the original defaults afterward so
the dev database isn't left in a test state.

---

## 2026-07-16 (28) — Real phone + OTP + MPIN authentication (first identity feature on real Laravel)

User asked to keep converting `resources/js/modules/*` off JS/localStorage
onto "standard Laravel structure." Given the scale (9 modules, ~6,000
lines, the entire app's business logic), confirmed two things before
starting rather than guessing: (1) real phone+OTP auth with a real
`users` table and real sessions - not just moving the JS's data store
while still trusting whatever phone number the client sends, which is
the same gap Deposits (entry 26) still has; (2) wallet balance +
transaction history is next, after auth.

Full design/architecture writeup is in DESIGN.md's "Real phone
authentication" section - the short version: new `phone_otps` table +
`mpin` column on `users`, new `PhoneAuthController` with 5 real Blade
pages (phone entry → OTP → set MPIN, or phone → MPIN for returning
users, or forgot-MPIN → OTP → set MPIN), all using the JS-free
`layouts.simple` layout Deposits already introduced. No SMS gateway
exists for this project, so OTPs are generated for real (random,
hashed, 5-minute expiry, 5-attempt lockout) but displayed back to the
user in an explicit "Demo mode" banner rather than actually texted -
documented as the one deliberate swap-in point for a real provider later.

**The one thing worth remembering if this area is touched again**: every
other feature in this app (wallet, rewards, referrals, nav gating) reads
identity from `localStorage`/`sessionStorage` via `window.isLoggedIn()`/
`window.getPhoneKey()`, written by `finalizeLogin()` in `auth.js`. Rather
than rewriting all of those call sites in the same pass (a much bigger,
separate job), this reused the **exact bridge pattern
`GoogleAuthController` already had working**: real login flashes
`phone_auth_bridge` into the session → `layouts/app.blade.php` emits
`window.__phoneAuthUser` → a new block in `auth.js` (mirroring the
existing Google bridge block line-for-line) folds it into the same
localStorage state via the *existing* `saveUser()`/`finalizeLogin()`
functions, not reimplementations of them. This is why the rest of the
app needed zero changes to keep working. `window.openAuth()` (called
from onclick handlers everywhere to gate actions behind login) now just
navigates to the real `/login` page instead of opening the JS modal.

**Deliberately not deleted this pass**: the old modal markup in
`auth-overlay.blade.php` and its ~500 lines of driving JS in `auth.js`
(`handleContinueClick`, `handleVerifyClick`, `handleSetMpinClick`,
`goToStep`, the OTP-input auto-advance listeners, etc.) are now dead
code - nothing calls `openAuth()`'s old modal-opening behavior anymore,
so none of it runs, but it's still sitting in both files. Left in place
rather than deleted in the same pass as shipping new, not-yet-battle-tested
functionality - `finalizeLogin()`/`saveUser()`/`getUserDB()`/`closeAuth()`
in the same file are still very much alive (the new bridge depends on
them), so a careless deletion pass risked breaking the working parts to
clean up the dead ones. Flagged explicitly as real follow-up work.

**One real, deliberate deviation from the old flow's exact spec**: the old
client-side signup generated a random fake name ("Karan Verma") since
there was never a name-entry step in the UI. A real `users` table
shouldn't get seeded with joke placeholder data, so `set-mpin` now asks
new signups for their actual name - a small, justified addition, not a
literal port of the old spec.

Verified end-to-end over real HTTP (not just direct model calls): full
signup (phone → demo OTP → name + MPIN → real session, confirmed the
resulting Home page actually carries `window.__phoneAuthUser` with
`isNewSignup:true`), returning-user login (phone routes straight to MPIN,
skipping OTP entirely; a wrong MPIN shows the right error and doesn't log
in; the correct one does, with `isNewSignup:false` on the bridge),
forgot-mpin's "no account found" validation, and confirmed directly in
the database that the OTP row is deleted after successful verification
(not left lingering) and the user row has a properly hashed MPIN and a
synthetic unique email. All other existing pages (Home, Explore,
Portfolio, Rewards, Profile, Add Money, Ops console) still return 200
after this change. Hit the same "POST redirect response is just a stub,
must GET the target to see real content/fresh CSRF token" test-script
gotcha as entry (26) twice more while verifying this - noting again since
it's now a recurring category of false alarm in this project's manual
HTTP testing, not a real bug each time it shows up.

`php -l` clean on every new PHP file. Learned from entry (26) this time:
rendered every new Blade view directly (not just `php -l`) before trusting
it, which caught nothing new here (the earlier `$errors`-undefined
warnings during that check were a Tinker artifact - `$errors` is normally
injected by middleware during a real HTTP request, confirmed once the
real HTTP tests above all rendered correctly).

---

## 2026-07-16 (27) — Removed the unused axios/bootstrap.js scaffold

User pointed at `resources/js/` and asked whether it needed removing,
prompted by the ongoing "move features off JS onto Laravel" direction
from entry (26). Checked first rather than assuming scope: confirmed
nothing in the app ever calls `axios`/`window.axios` (grepped every `.js`
and `.blade.php` file — zero hits outside `bootstrap.js` itself). Asked
the user to confirm scope given the ambiguity between "remove this one
dead file" and "keep migrating more of the app off JS" (a much larger,
multi-session undertaking) — confirmed just the former.

Removed the `import './bootstrap'` line from both Vite entries
(`app.js`, `admin.js`), deleted `resources/js/bootstrap.js`, and ran
`npm uninstall axios` (27 packages removed, including axios's own
dependencies like `follow-redirects`/`form-data`/`proxy-from-env` that
were being bundled along with it). Confirmed via the build output that
the previously-separate `bootstrap-*.js` chunk (~46KB, `follow-redirects`
etc.) is gone entirely, not just unused-but-present — this was real dead
weight in every page load, not just source-level clutter. `node --check`
clean, `npm run build` clean, verified live via HTTP that Home and the new
`/add-money` page both still return 200 and `window.axios` no longer
appears anywhere in the rendered output.

**Left alone, deliberately**: the rest of `resources/js/modules/*` (navigation,
animations, auth, app-state, i18n, toast, settings, admin) — none of that
is dead code, it's what makes the mobile SPA (tab switching, wallet
display, auth flow, etc.) function, and none of it has been migrated to
server-side Laravel yet. Only Add Money (entry 26) has moved so far.

---

## 2026-07-16 (26) — Rebuilt Add Money on real Laravel (DB, controllers, routes) instead of JS

Same-day follow-up to entry (25). User pushed back: don't build features
in JS/localStorage, use real Laravel structure (migrations, models,
controllers, routes, server-rendered Blade views) instead. Confirmed two
scope decisions before rebuilding: (1) traditional full-page Laravel form
for the Add Money submission itself (not an AJAX-backed modal), and (2)
wallet balance moves to a real database table too, so admin approval
credits it directly and reliably.

**Deleted outright** (not kept alongside): `resources/js/modules/add-money.js`,
`resources/views/components/add-money-modal.blade.php`, its import in
`app.js`, its `<x-add-money-modal />` include in `layouts/app.blade.php`,
and every deposit-related addition to `resources/js/modules/admin.js`
from entry (25) (the `pendingDeposits`/`utrRegistry` localStorage
handling, `renderDeposits()`, `approveDeposit()`/`rejectDeposit()`, the
deposit-filter-tab listeners, and the "Deposits" log tab) — `admin.js` is
now back to exactly its pre-(25) shape plus nothing deposit-related.

**New real backend**:
- Migrations + models: `deposit_requests` (`App\Models\DepositRequest`)
  and `wallet_balances` (`App\Models\WalletBalance`), both phone-keyed
  (there's no real `user_id` to key on - phone/OTP login is entirely
  client-side; only Google OAuth users get a real `Auth::user()`).
- New module `app/Modules/Deposits/` — `DepositRequestController@create`/`@store`,
  `GET|POST /add-money`, real server-side validation, a genuinely
  JS-free page (`resources/views/layouts/simple.blade.php`, a new minimal
  layout loading only compiled CSS, no `app.js`).
- `AdminController` gained `deposits()`/`approveDeposit()`/`rejectDeposit()`,
  and a **new, separate route** `GET /{slug}/deposits` + its own Blade
  view (`Admin::deposits`) — deliberately not another `[data-panel]` tab
  inside `/dashboard` like the other four panels, since Approve/Reject
  here are real `<form method="POST">` submissions to real routes, not
  JS click handlers.
- Extracted a shared `resources/views/components/admin-sidebar.blade.php`
  from `dashboard.blade.php`'s previously-inline sidebar markup, since two
  real pages now need it. It renders "Deposit requests" as a real link on
  both pages; the other four items render as `[data-panel]` JS-toggle
  buttons only while already on `/dashboard`, and as plain links back to
  it from `/deposits` (a minor known rough edge: linking to a specific
  *panel* from the deposits page just lands on `/dashboard`'s default
  Wallet-adjustment panel, not the exact one clicked — not worth more
  complexity for a secondary nav path).

**UTR uniqueness moved from an in-memory JS registry to a database
constraint** — specifically a **partial unique index**
(`CREATE UNIQUE INDEX ... ON deposit_requests (utr) WHERE status !=
'rejected'`), not a plain `->unique()` column. This was a real bug caught
mid-build: migrating straight to `$table->string('utr')->unique()` would
have permanently blocked any UTR after a single rejection, regressing the
"rejected UTRs can be resubmitted" behavior entry (25) had already
established as correct. Caught by working through the reject-then-resubmit
scenario deliberately rather than just porting the old column definition
across. The controller's validation (`Rule::unique(...)->where(status !=
rejected)`) is scoped to match, so the friendly validation message and the
raw DB constraint agree. This partial-index approach is SQLite-specific
(this app's actual database) — flagged in DESIGN.md that MySQL has no
equivalent and would need the check moved fully into application logic.

**Deliberately not unified**: `wallet_balances` (new, DB-backed) is now a
*second*, separate wallet balance from the pre-existing
`bachatpe_wallet_balance_{phone}` localStorage key that Home's balance
display, rewards claims, and referral-commission claims still use.
Making Home read the real DB balance would require either real
server-side session auth for phone/OTP users (a much bigger rework, not
asked for) or a client-side bridge (exactly the "JS structure" the user
asked to move away from for *this* feature). Documented plainly in
DESIGN.md rather than silently leaving two wallet balances undocumented —
this is the one genuine architectural gap left by scoping the change to
just the Add Money feature.

**A real bug was found and fixed during verification, not before**:
`php -l` passed on every touched file (as it always does for Blade files —
directives aren't valid raw PHP, so `php -l` can't see them), but
rendering `Admin::deposits` threw a Blade compile error. Root cause:
`admin-sidebar.blade.php` had `Deposits@if ($pendingDepositCount > 0)`
with no space between the word and the directive — Blade's directive
regex doesn't fire when `@if` is immediately preceded by a word character,
so it silently left `@if(...)` as literal text while its matching
`@endif` (preceded by `>`, a non-word character) *did* compile, producing
a mismatched-directive PHP parse error. Fixed by adding a space
("Deposits @if"). Caught by actually rendering the view via
`app('blade.compiler')->compileString()` + `php -l` on the compiled
output, not by reading the source — the established lesson from this
project (`php -l` on `.blade.php` files is a weak check) held again here,
this time for a genuinely new failure mode (word-adjacency, not
mismatched-count).

**Verified end-to-end over real HTTP**, not just via direct model calls:
submitted a real UTR through `/add-money`, confirmed resubmitting the same
UTR is rejected with the friendly message, logged into the Ops console,
approved the request via the real HTML form button, and confirmed
`wallet_balances` was credited server-side and the deposit's status
flipped to `approved`. Also confirmed via direct model manipulation that a
*rejected* UTR can be resubmitted while a still-pending one cannot be
duplicated (the partial-index behavior specifically). Hit one testing-tool
artifact along the way worth remembering: **POSTing to a URL with a
trailing slash the app doesn't expect gets silently 301-redirected, and
both curl and PowerShell's `Invoke-WebRequest` (with default settings)
convert POST-to-GET when following a 3xx redirect, silently dropping the
POST body** — this looked exactly like a broken login (419 errors, no
visible cause) until traced to the extra trailing slash in the test
script itself, not a real app bug. Worth checking test-script URLs for
this before assuming an HTTP verification failure is a real regression.

`php -l` clean on every PHP file, `node --check` clean on the reduced
`admin.js`/`app.js`, `npm run build` clean, migrations run cleanly against
the dev SQLite database (rolled back and recreated once mid-build to fix
the partial-index issue before any real data existed).

---

## 2026-07-16 (25) — Manual UPI "Add Money": UTR entry + admin verification

User asked for an offline/manual UPI deposit flow: show a UPI-app picker
(Google Pay/PhonePe/Paytm/other) like a real payment app, let the user
submit the 12-digit UTR after paying manually, block reuse of an
already-used UTR, and give admin a way to manually verify and approve
these before the wallet is credited.

This app has no real payment gateway (client-side/localStorage only), and
the Home page's "Add Money" button turned out to have **no onclick
handler at all** — it was purely decorative, so this was a clean build
rather than a migration of existing behavior.

**New files**:
- `resources/views/components/add-money-modal.blade.php` — a 3-step
  centered-card modal (same visual shell as `auth-overlay.blade.php`:
  header with back/close, `data-am-step` panels toggled by JS): pick a
  UPI app → pay-to-VPA instructions + UTR input → "submitted, pending
  review" confirmation. Included globally via `<x-add-money-modal />` in
  `layouts/app.blade.php`, next to `<x-popups />`.
- `resources/js/modules/add-money.js` — step logic, UTR format validation
  (`^\d{12}$`, the real NPCI UTR length), and the submit handler.

**Data model** (new localStorage keys, shared verbatim between the main
app bundle and the separate admin Vite entry — same pattern as wallet
balance/settings already use):
- `gullakpe_pending_deposits` — a **global** array (not per-phone) of
  `{ id, phone, amount, method, methodLabel, utr, status, submittedAt, reviewedAt }`,
  `status` one of `pending`/`approved`/`rejected`.
- `gullakpe_utr_registry` — a **global** object keyed by UTR string,
  `{ [utr]: { requestId, status } }`, used purely for the duplicate check.

**UTR uniqueness is global, not per-user** — deliberate choice. The user's
ask ("restrict so they can't reuse the same UTR") reads most naturally as
one user double-submitting their own UTR, but a UTR corresponds to one
real bank transaction regardless of who submits it, so scoping the check
to "this phone number" would still let user A's UTR be reused by user B.
Checked globally against `gullakpe_utr_registry` instead — a strict
superset of the literal ask that closes the same-user case too. A
rejected request releases its UTR back for resubmission (a rejection
means *that claim* didn't check out — wrong amount, typo — not
necessarily that the UTR itself is fraudulent); approved/pending UTRs stay
locked forever.

Submitting a UTR does **not** touch the wallet balance — it only creates
the pending request/registry entries and adds a `status: 'Pending'`
transaction (`window.addTransaction`, reusing its existing status field
rather than adding a new mechanism) so the user sees it in their history
immediately. Crediting only happens on admin approval.

**Admin side** (`app/Modules/Admin/Views/dashboard.blade.php` +
`resources/js/modules/admin.js`): new "Deposit requests" panel following
the exact `[data-panel]`/`[data-panel-content]` convention the other 4
panels already use (zero changes needed to the existing panel-switcher —
it's already generic over any `data-panel` value). Pending/Approved/
Rejected filter tabs (same visual pattern as the existing log-tabs).
Approve credits the wallet via the same `getWallet`/`setWallet` helpers
the Wallet-adjustment panel already uses, flips the matching transaction
to `Completed` by matching on the transaction `id` (generated once in
`add-money.js` and threaded through both the transaction and the deposit
record specifically so admin approval can find and update the right
entry), and marks the UTR `approved` (locked forever). Reject flips the
transaction to `Failed` and releases the UTR. Both log to a new 3rd log
tab ("Deposits", `gullakpe_admin_deposit_logs`), added alongside the
existing Referral/Commission tabs — the "Clear logs" button's confirm text
and clear action were updated to include it. A pending-count badge
(same visual pattern as the main app's `sidebar-rewards-badge`) shows on
both the desktop sidebar item and the mobile tab.

Verified: `php -l` on every touched Blade file, `node --check` on
`admin.js`/`add-money.js`/`app.js`, grepped both JS files to confirm the
deposit-record field names and localStorage keys agree exactly, `npm run
build` clean. **Not verified live** — Apache/XAMPP wasn't running in this
session (`localhost` unreachable, no Windows service registered for it
either) and the user asked to skip live verification rather than start it
this time. This is the first change this session shipped without an HTTP
check; worth actually clicking through the full flow (submit a UTR as a
user, approve/reject it as admin, confirm the wallet updates and the UTR
is correctly blocked on retry) once Apache is up.

---

## 2026-07-16 (24) — Stripped the app's "gamification layer" for a banking-minimal feel

User said the app "feels vibe coded" and asked to remove unnecessary
animations/clutter app-wide for a "banking level, minimal" feel — a bigger
ask than entry (8)'s earlier motion/glow pass, which only toned down glows
and eased-out the springy curves but left a large layer of decorative and
game-like effects untouched. This pass removed that layer outright rather
than toning it down further.

**Removed entirely (not just de-animated):**
- The Home hero's fake "Live Ticker" — a scrolling marquee of fabricated
  social proof ("Rahul unlocked iPhone Goal", "Neha reached VIP Gold").
  Fake activity feeds are a growth-hacking pattern, not something a bank
  states as fact.
- The 40-coin physics-simulated "flying coins" burst
  (`window.triggerCoinAnimation` in `app-state.js`) shown on every referral
  commission claim — full-screen blur overlay, 40 gravity-simulated coin
  sprites, a phone vibration pattern, and a 3-note synthesized chime
  (`playChimeSound`, Web Audio oscillators). Replaced with a single light
  haptic tap; the existing success popup + toast were always the real
  confirmation, the coins were pure spectacle on top.
- All 4 `confetti()` bursts (plan purchase, cashback claim, commission
  claim) — removed the calls and the now-unused
  `canvas-confetti` CDN `<script>` from `layouts/app.blade.php`.
- The 3-particle gold-sparkle burst inside `showPremiumToast()`, the
  3-particle "₹" burst on tapping a Home quick-amount shortcut
  (`#currency-particles`), the 3 ambient floating dust particles on the
  Home balance card, and 4 floating gold coin dots around the Rewards claim
  button (`.floating-coin-decor`) — all decorative particle effects with no
  informational content.
- 3 animated emoji badges on Home's quick-amount buttons (⚡🚀🔥, each with
  its own bounce/pulse/rotate keyframe) — redundant with the "Most
  Popular"/"Fast Deposit" text tags already on the same buttons.
- The conic gold rays / soft glow / light-overlay background layers behind
  the Rewards QR code (`.premium-radial-rays`, `.premium-qr-glow`,
  `.premium-light-overlay`) — decorative only, read as a casino backdrop
  behind a referral QR code.
- The app-wide "released-glow" halo (`animations2.js`): every single
  button/card/nav-item in the app got a green box-shadow ring bloom
  100-800ms after every tap, on top of the ripple. This fired everywhere,
  all the time — likely the single biggest systemic contributor to the
  "vibe coded" feel. Kept the ripple itself (a standard, restrained tap
  pattern, already vetted in entry (8)); removed only the extra glow layer.
- Dead CSS with zero usage found via grep before deleting: `.vip-badge-glow`,
  `.portfolio-glow`, `.particle`/`move-particle`, `.floating-center-btn`,
  `.animate-spin-slow`, `.mesh-gradient-custom`, `gradient-shift`,
  `float-up-down` keyframes.

**Converted from infinite/eternal to static or one-shot, where the element
itself is legitimate but the perpetual motion wasn't:**
- Home's verified-profile badge — was pulsing forever (`verifiedPulse`,
  defined twice, once dead); now a static glow.
- Rewards' "Claim Bonus" button — was `animate-pulse-soft` (breathing
  scale + glow) for as long as it's claimable, sometimes indefinitely;
  now static, still visually a primary CTA via its gradient/shadow, just
  not eternally pulsing at the user.
- Rewards' referral-steps timeline connector — was an infinite
  opacity-pulse (`animate-timeline-line`); now a fixed `opacity-25` line.
- The "slide to invest" track's `::before` — an infinite light-sweep shine
  every 2.4s on the primary investment CTA (`slideShine`); removed, track
  is now a flat gradient.
- The success state of that same track — was `successPulse infinite
  alternate` (a box-shadow that keeps breathing after a successful
  investment) layered under the one-shot `successIsland` scale-in; kept
  only the one-shot settle-in.
- The 4 popup icon-circle glows (success/amber/red/teal, `popups.blade.php`)
  — were infinite pulsing halos behind every confirmation/error modal's
  icon; now static tinted glows, same convention as `.gold-glow` from
  entry (8) (color retained as a state signal, animation removed).
- A 4th occurrence of the overshoot "bounce" easing
  (`cubic-bezier(0.175, 0.885, 0.32, 1.275)`) that entry (8) missed, found
  in `showPremiumToast()`'s icon reveal — replaced with the same
  ease-out-quint used everywhere else, and dropped a `rotate(-45deg)`
  icon-spin wobble to a plain scale-in.
- An `animate-bounce` (Tailwind's infinite bounce) on the "Goal Activated"
  success checkmark — removed, checkmark is now static.

**Deliberately left alone** (one-shot or state-driven, not idle decoration):
the Material-style tap ripple itself, all page/modal entrance transitions
(`fadeInUp`, `slideUpFade`, `flip-in`, `animate-count-up`), loading
indicators (`dots-anim`, skeleton shimmer, global-loader pulse — these
signal "please wait," which is the one category of infinite animation a
banking app legitimately needs), the auth screen's `magic-slide` button
shimmer (only visible during an actual loading state, not idle), one-shot
error shakes, and `.premium-card`/`.interactive-element` hover-lift and
tap-scale (standard, restrained interaction feedback).

Verified: grepped across every `.blade.php`/`.js`/`.css` file for each
removed class/keyframe name to confirm zero remained (2 harmless hits: a
comment mentioning the old name, and a defensive `.replace()` call that
strips a class that's never added — both no-ops, left alone). `php -l` and
`node --check` clean on every touched file. `view:clear` + `npm run build`
clean — CSS shrank 170.66kB → 163.23kB, JS 156.75kB → 152.59kB, confirming
this removed real code, not just usage. Verified live via HTTP on both
Home and Rewards: all removed markers absent, both pages still render
their real content correctly.

**Not done, flagged rather than guessed at**: "unnecessary padding" wasn't
addressed here — it's too subjective to act on blindly across a multi-page
app without a screenshot or a specific page pointed out, unlike the
animation removals above which were each independently verifiable as
dead/infinite/decorative from the code itself. If specific spacing still
feels off after this pass, point at a page or screenshot the way prior
vague-UI reports in this project were resolved (login popup, bottom nav,
chip truncation).

---

## 2026-07-16 (23) — Folded the Settings tab back into Profile as list items

User reconsidered entry (19)'s standalone Settings tab: didn't want it as
its own section, wanted each option living directly inside Profile instead.

Deleted the dedicated tab entirely rather than just hiding it: removed
`app/Modules/Settings/Views/settings.blade.php`, its route (`GET /settings`),
and `SettingsController.php`; removed `@include('Settings::settings')` from
`dashboard.blade.php`; removed the desktop sidebar's separate "Settings" nav
item and the `<hr>` divider above it (no longer needed with nothing below
it). Kept `app/Modules/Settings/Views/modals.blade.php` — the six edit
modals themselves are unrelated to *where* they're launched from, so no
reason to rebuild them.

In `app/Modules/Profile/Views/profile.blade.php`, replaced the single
"Settings" list item with six individual list items (Profile Information,
Security, Notifications, Language & Region, Payment Methods, Privacy &
Data), matching the existing list-item style (icon + label + chevron,
same as Bank account/Statements), each calling
`window.openSettingsModal(name)` directly instead of switching tabs first.
Moved `@include('Settings::modals')` from the old settings tab into
`profile.blade.php` (modals are just fixed-position overlays, so their
position in the DOM doesn't matter, only that they're included once).
Kept the `id="settings-current-lang"` element (now inline next to the
Language & Region row) since `settings.js` already looks for it optionally.

Verified: grepped for `Settings::settings`, `tab-settings`,
`newSwitchTab('settings')`, and `data-tab="settings"` across `app/` and
`resources/` — zero remaining. `php artisan route:list | grep settings` —
empty, confirming the dead route is gone too. `php -l`/`node --check` clean
on every touched file, `view:clear` + `npm run build` clean, and verified
live via HTTP: `tab-settings` absent, all six option rows and their modals
present inside the Profile page response, sidebar's `data-tab="settings"`
gone.

---

## 2026-07-15 (22) — Removed the green underline active-indicator on Explore chips

User asked to remove the small green underline bar (`.active-indicator`,
`w-4 h-0.5 bg-[#3CCF91] rounded-full shadow-[0_0_8px_#3CCF91]`) shown under
the selected filter chip. **Fourth consecutive fix to this same chip row
needing all three copies of the logic checked** (same pattern as entries
(17), (20), (21)): removed the static `<span class="active-indicator ...">`
from the "All Goals" chip in `explore.blade.php`, the add/remove logic in
that file's inline `window.syncCategoryUI` script, and the equivalent
add/remove block in `navigation.js`'s `window.filterPlans`. Chips now only
signal active state via background/border/font-weight, no separate
indicator element.

Grepped both files for `active-indicator` afterward — zero remaining.
`php -l` and `node --check` clean, `view:clear` + `npm run build` clean,
and verified live via HTTP that the rendered page has zero
`active-indicator` occurrences while chip labels ("All Goals", etc.) still
render correctly.

---

## 2026-07-15 (21) — Fixed a regression from my own previous fix: chip labels were truncating

User's "circles cutting the text" turned out to mean the filter chips
themselves (pill/circle-ended shape) were clipping their own labels —
screenshot showed "All G", "Tren", "Fast R", "Begi", "Veri" instead of the
full text. Root cause: none of the 5 chip buttons had `shrink-0`. Their
container is `flex ... overflow-x-auto` — `overflow-x-auto` only kicks in
once content overflows, but the browser's default `flex-shrink: 1` on flex
children tries to shrink them to fit *first*, so every chip's text got
compressed/clipped instead of the row scrolling horizontally. This is a
textbook flexbox gotcha and may well have been present before today's
chip-height fix too, just not reported until now — not something introduced
by that fix, but very plausibly made more noticeable by it (any accidental
extra width from `h-11` vs the old auto-height would tighten the squeeze
further).

Added `shrink-0` to all 5 chip buttons. Same "logic exists in 3 places"
situation as the last two chip fixes — applied it to the static markup, the
inline `<script>` in `explore.blade.php`, and the separate copy in
`navigation.js`'s `window.filterPlans`, then grepped for
`px-5 h-11` without `shrink-0` across both files to confirm zero remained
before rebuilding.

This is now the third consecutive chip-row bug that required checking all
three copies of the restyling logic — worth actually refactoring this into
one shared function at some point rather than continuing to patch three
places in lockstep, though that's a larger, riskier change than a quick fix
and wasn't asked for here.

---

## 2026-07-15 (20) — Fixed the 3 confirmed chip bugs (found a 3rd copy of the logic)

Followed up on the Explore page's top filter-chip audit: fixed the dead
`hover:scale-102` (not a valid Tailwind utility — no CSS was ever generated
for it), the sub-44px touch target (`py-2.5` → `h-11`), and inconsistent
icon sizing (4 of 5 icons had no explicit size, inheriting the chip's
font-size by accident) in `app/Modules/Explore/Views/explore.blade.php`.

**Same "duplicate logic, fix one copy and miss another" pattern as entry
(17), a third time now**: this chip row's restyling logic exists in *three*
places — the static initial HTML, an inline `<script>` in
`explore.blade.php` (`window.syncCategoryUI`), and a separate copy in
`resources/js/modules/navigation.js` (`window.filterPlans`, the function
actually bound to each chip's `onclick`). Fixed the static markup first,
verified via `grep` across every JS/Blade file for remaining `scale-102`
before calling it done — which is exactly what caught the `navigation.js`
copy. Icon-size fix only needed the static markup (the JS only recolors
icons via `.className.replace()` on a specific color token, never
overwrites the whole class list, so it can't strip a size class once
present there).

**Pattern worth remembering going forward**: this app has several UI
elements whose "active/inactive" restyling is implemented via full
`className = "..."` string replacement in more than one JS location rather
than a single shared function or CSS class toggle. Any fix to one of these
elements needs an explicit `grep` for the same literal class string
elsewhere before considering it done — checking only the file that looked
relevant isn't sufficient, as this and entry (17) both demonstrated.

Verified live: `h-11` on all 5 chips, zero remaining `scale-102` anywhere in
the app, all 5 icons carry explicit `text-[14px]`. `npm run build` clean.

---

## 2026-07-15 (19) — Settings page: cards + edit modals + Notyf toasts

User confirmed scope first: main mobile app (not admin), a brand-new
dedicated page (not a reorganization of Profile's existing list), Notyf for
toasts. Explicitly described as a preview/showcase feature, not real
persistence — the page says so on itself.

New 8th module (`app/Modules/Settings/`) alongside the existing 7, following
the same `ModuleServiceProvider` convention. 6 cards → 6 modals: Profile
Information, Security, Notifications, Language & Region, Payment Methods,
Privacy & Data. Reached from Profile's "Settings" list item, which turned
out to already exist in the markup but was never wired to anything — just
connected it rather than adding a duplicate. Also added to the desktop
sidebar (below a divider, as a secondary item) but deliberately not the
mobile bottom-nav, which is already tight at 5 items.

Installed Notyf (`resources/js/modules/toast.js`, `window.toast.success/error`),
configured with brand colors. Scoped to only the new Settings actions —
didn't touch the app's existing hand-rolled toast (`showGlobalSuccess` in
auth.js) or the admin panel's toast div, both keep working as before for
their own call sites. Confirmed via the Vite manifest that Notyf's
auto-split CSS chunk is correctly linked to the `app.js` entry and loads
automatically.

One real exception to "preview only": the Language card actually drives the
app's existing i18n system (`window.toggleLanguage()`) rather than faking
it — no reason to build a second fake switcher next to a working real one.

**Avoided repeating a pre-existing bug**: almost gave the page's back button
a `window.previousTab`-based "return to last page" behavior, matching
`goBackFromPlanDetails()`. Checked first and found `previousTab` is only
ever set inside a dead, commented-out block in `navigation.js`
(lines 1-173) — so that mechanism has never actually worked, and
`goBackFromPlanDetails()` silently always falls back to its hardcoded
default. Pre-existing, unrelated to this task, not fixed here — but avoided
building new code on top of a mechanism already confirmed broken. Hardcoded
Settings' back button to go straight to Profile instead.

Verified via HTTP: `tab-settings` present, all 6 `data-settings-modal`
elements present, sidebar's `data-tab="settings"` present, `npm run build`
clean (68 modules, two CSS chunks — main `app.css` and Notyf's auto-split
chunk, both correctly listed under the `app.js` entry in the manifest).

---

## 2026-07-15 (18) — Ops console: sidebar nav instead of one long grid

User asked for the admin panel's 4 feature areas (wallet adjustment,
simulations, referral program settings, activity logs) to be organized via
a sidebar, since it's got multiple distinct options now. Restructured
`app/Modules/Admin/Views/dashboard.blade.php` from a 2-column grid showing
everything at once into a sidebar + one-panel-at-a-time layout — kept every
existing element ID (`wallet-adjust-form`, `btn-simulate-commission`,
`setting-referral-enabled`, etc.) exactly as-is, so the working JS logic in
`admin.js` needed zero changes to its actual functionality, only a new
panel-switcher on top (`[data-panel]` buttons, `[data-panel-content]`
sections, one small click handler). Same background-tint active-state
convention as the main app's sidebar (`.ops-nav-item.is-active`, no
left-border stripe). Below `md:`, the sidebar is replaced by a horizontal
scrollable tab row in the mobile header, sharing the exact same
`data-panel` buttons and click handler — one code path drives both, unlike
the main app's bottom-nav/sidebar split which needed the same fix applied
twice (see entry (17)).

Verified via HTTP (full login flow): dashboard now has 8 `[data-panel]`
buttons (4 desktop sidebar + 4 mobile tabs) and 4 `[data-panel-content]`
sections, `npm run build` clean.

---

## 2026-07-15 (17) — Actually fixed sidebar active-highlight (wrong function last time)

User reported the sidebar items never visually activate when switching
pages, even though the page content correctly switches. Root cause: this
file has **two separate, similarly-shaped** active-highlight blocks -
`window.goToExploreTab` has one (~line 20-55), and the real
`window.newSwitchTab` (~line 182, the one every single nav click actually
calls, mobile and sidebar alike) has its own, separate one (~line 208). In
entry (12) I found and fixed the first one and never noticed the second —
so the fix I shipped was live in a function that isn't the one driving
normal navigation, which is exactly why the symptom persisted despite that
"fix". Applied the same change (broaden the selector to
`#bottom-nav button, #desktop-sidebar button`, match via `data-tab` instead
of `btn.id`, toggle `.is-active` on `.sidebar-nav-item`) to the actual
`newSwitchTab` block this time. Verified the resulting logic
(`sidebar-nav-item`) is present in the built JS bundle and the live page
serves it.

Lesson for next time: when a fix doesn't take effect, check whether there's
a *second* place doing the same thing before assuming the first fix was
wrong in its logic - it can be the right fix in the wrong location.

---

## 2026-07-15 (16) — Add Money button text color

Home page's "Add Money" button (`home.blade.php`) sets `text-[#04242F]`
(dark navy) on the button itself; the "Add Money" label span had no color
override so it inherited that dark text against the button's mint-green
gradient background. Added `text-white` to the span. Left the plus-icon
dark, since it sits in its own light translucent circle and wasn't part of
the complaint.

---

## 2026-07-15 (15) — Google login: first real database-backed feature

User confirmed scope first: they already have Google Cloud OAuth credentials
(not yet given to me — placeholders in `.env` for now); yes to adding a real
`users` table (first one in this app beyond the unused Laravel skeleton
default); yes to bridging Google-authenticated users into the existing
localStorage-simulated wallet/rewards/referral system rather than keeping
them separate.

Installed `laravel/socialite`. Discovered while adding a users migration
that the stock `0001_01_01_000000_create_users_table.php` already creates
`users` (and `sessions`, correcting a wrong claim in SECURITY.md from
earlier this session) — checked `php artisan migrate:status` first to
confirm what's actually been run before adding anything, added a new
*additive* migration (`google_id`, `avatar`, `phone` nullable/unique) rather
than touching the existing one.

Built `app/Modules/Auth/Controllers/GoogleAuthController.php`
(redirect/callback/linkPhone) + routes, real `Auth::login()` session. The
non-obvious part was bridging this into the app's existing all-client-side
auth state (see DESIGN.md's "Google login" section for the full mechanism):
Google gives no phone number, but the entire rest of the app is phone-keyed,
so a first-time Google sign-in reuses the *existing* phone/OTP UI to collect
one rather than building new UI, and `finalizeLogin()` (already the single
choke point every login path runs through) picks up a stashed Google profile
at that point to use real name/email and persist the phone server-side via
a new `POST auth/google/link-phone` endpoint. Returning users (phone already
linked) skip straight through.

Redirect URI is an explicit full `.env` value
(`GOOGLE_REDIRECT_URI=http://localhost/gullakpe/gullakpe-laravel/auth/google/callback`),
not derived from `route()`/`APP_URL` — `APP_URL` is just `http://localhost`
while the app lives under `/gullakpe/gullakpe-laravel/`, same class of
mount-path bug fixed for i18n and admin assets earlier this session, just
caught before it shipped this time instead of after.

Replaced the "Continue with Google" button's `alert('Google Auth triggered')`
stub with a real `route('auth.google')` link.

**Verified with blank placeholder credentials** (real ones not yet
provided): hit `/auth/google` via HTTP, confirmed it redirects all the way
to `accounts.google.com`, which responds "Missing required parameter:
client_id" — proves the entire chain (route → Socialite → config →
redirect URL construction) is wired correctly; only the actual credential
values are missing. **Not yet verified**: the callback → user-creation →
localStorage-bridge path, since that needs a real consent screen completion
with real credentials.

---

## 2026-07-15 (14) — Fixed real overflow bug from the desktop sidebar work

User reported the desktop pages were oversized enough to trigger horizontal
scroll ("bottom scroll activating") and that switching between pages via
the sidebar didn't seem to work.

Found a concrete bug in entry (12)'s own work: `layouts/app.blade.php`'s
`<main>` had `w-full md:ml-[248px]` — `w-full` is 100% width, and a margin
added on top of that pushes the box 248px past the viewport's right edge
(margin isn't included in a `width:100%` calculation the way padding is).
That's a textbook horizontal-overflow bug, and it matches the reported
symptom exactly. Fixed by switching to `md:pl-[248px]` (padding-left)
instead of margin — padding IS included inside `width:100%` under
`box-sizing:border-box` (Tailwind's preflight default), so it can't push
the box past its own bound. Added `overflow-x-hidden` on `<main>` too, as a
safety net against any other width mistake having the same effect.

**Second complaint (pages "not activating") — investigated, not confirmed
as a separate bug.** Re-read the actual tab show/hide logic in
`navigation.js` (`window.newSwitchTab`, distinct from the nav-highlight-sync
code touched in entry (12)) end to end: it hides every `.tab-content` then
shows the target by ID, unrelated to anything changed for the sidebar.
Couldn't find a code-level cause. Since `position:fixed` elements (the
sidebar) aren't affected by a page's horizontal scroll, the overflow bug
above shouldn't have literally blocked sidebar clicks from registering —
but a broken, overflowing layout could easily make a correctly-switched tab
*look* like nothing happened. Fixed the confirmed bug; flagged in my
response that if pages still don't switch after this, it needs a screenshot
or a specific repro (which page, what's on screen) rather than another
guess — same pattern as every other hard-to-pin-down UI bug this session.

Verified: `npm run build` clean, compiled CSS confirmed `padding-left:248px`
present and the old `margin-left:248px` gone.

---

## 2026-07-15 (13) — Admin login: progressive lockout + dedicated security log

User asked specifically to prevent brute force on the admin panel. The
existing protection (from when the panel was first built, entry (11)) was a
flat Laravel `RateLimiter`: 5 attempts/60s per IP. Recognized that's weak on
its own — after the 60s window lapses an attacker just resumes at the same
cost, so it caps the RATE but not the total attempts over time (~7,200
guesses/day is still available against one password).

Replaced with a persistent per-IP failure counter (`Cache`, 24h TTL,
survives across individual lockout windows unlike a plain rate limiter) that
escalates: 5 fails → 1 min lockout, 10 → 15 min, 15 → 1 hour, 20 → 24 hours.
Also added a dedicated `admin_security` log channel
(`config/logging.php` → `storage/logs/admin-security-{date}.log`, 90-day
retention) recording every attempt (IP, failure count, lockout duration,
user agent on failures) — separate from the general app log specifically so
reviewing admin access doesn't require sifting through unrelated noise.

Verified live, not just by reading the code: sent 6 consecutive wrong
passwords via HTTP — attempts 1-4 correctly showed "Incorrect password",
attempt 5 correctly triggered "Too many attempts. Try again in 1 minute(s)",
attempt 6 showed the countdown ("59 second(s)"). Confirmed
`storage/logs/admin-security-2026-07-15.log` recorded all 6 events with the
right failure counts and lockout values. Cleared the cache afterward so my
own test lockout doesn't carry over.

**Documented, not silently accepted**: this is still IP-scoped, so a
distributed attempt (botnet/rotating proxy) gets a fresh tier-1 budget per
IP — proportionate for a single-operator tool, not sufficient for a real
production deployment (SECURITY.md spells out what to add: a global
cross-IP cap, a CAPTCHA-style challenge). Same "documented tradeoff, not a
gap pretending to be a fix" pattern as the password-hashing decision from
entry (11).

---

## 2026-07-15 (12) — Desktop layout: sidebar nav + two-tier page treatment

User asked for a genuinely different ("unique") desktop design across Home
and the other pages, not a centered phone frame — confirmed scope first:
full re-layout (sidebar nav, multi-column, not just centering) across all
pages in one pass, no per-page checkpoint.

Built: `resources/views/components/desktop-sidebar.blade.php` (persistent
left nav, `md:` only, replaces the bottom tab bar which gained `md:hidden`),
wired into `layouts/app.blade.php` (`<main>` gets `md:ml-[248px]`). Had to
switch `navigation.js`'s active-tab matching from `btn.id` to a `data-tab`
attribute since the two navs can't share element IDs — full detail in
DESIGN.md's new "Desktop layout system" section, including why the active
indicator is a background tint and not a left-border stripe (banned pattern).

**Was honest about depth vs. breadth rather than claiming full parity**:
Home got an actual bespoke reflow (its Plans carousel became a real 3-column
grid at `md:`). The other 5 pages did not get that same depth — investigated
converting Explore's plan-card list the same way and found the cards have no
shared wrapper `<div>` to target, meaning doing it properly means inserting
a new wrapper spanning a large stretch of an 1806-line file with no browser
available to verify the result, which is a real risk of a silent breakage.
Applied the safe version instead: all 5 pages had zero `max-width` anywhere,
so gave them a shared floor (`.tab-content:not(#tab-home) { max-width:860px }`
at `md:`) so they don't stretch edge-to-edge and look broken, without the
bespoke multi-column treatment Home got. The `:not(#tab-home)` exclusion is
load-bearing — omitting it would silently shrink Home's own wider grid back
down since both share the `.tab-content` class.

**Real follow-up work, not done**: bespoke desktop reflows for Explore /
Portfolio / Rewards / Profile / Plan Details, each needing the same kind of
structural investigation Explore's plan-card list needed here. Flagged this
explicitly rather than letting a lighter-touch fix pass as equivalent to
Home's — same principle as every other honesty-over-appearance call this
session (the admin panel's "Simulate" actions, the earlier CSS/scrolling
audit).

Verified: `npm run build` clean, live HTTP check on `/` (Home) and `/explore`
both 200, compiled CSS confirmed the `:not(#tab-home)` exclusion survived
minification.

---

## 2026-07-15 (11) — Built a real Ops Console to replace the removed admin modal

Confirmed scope with the user first: rebuild the same feature set as the
removed debug modal (wallet adjustment, commission/referral simulation,
logs) as a proper page rather than a modal; add a real login gate (not just
an obscure URL — user agreed this matters); make the URL configurable via
`.env` rather than hardcoded.

**New files**: `config/admin.php`, `app/Http/Middleware/AdminAuthenticate.php`,
`app/Modules/Admin/{Controllers/AdminController.php,routes.php,Views/{login,dashboard}.blade.php}`,
`resources/views/layouts/admin.blade.php` (separate from the mobile
`layouts/app.blade.php` — no bottom-nav/global-loader chrome), `resources/js/admin.js`
+ `resources/js/modules/admin.js` (a second Vite entry, `vite.config.js`
updated — admin doesn't load the mobile SPA's navigation/i18n bundle).
`.env`/`.env.example` gained `ADMIN_PANEL_SLUG` / `ADMIN_PANEL_PASSWORD`, and
`SESSION_DRIVER` changed `database` → `file` (no `sessions` migration existed,
and file-based sessions are fully adequate for this scope — see SECURITY.md).

Full feature-by-feature writeup is in DESIGN.md's "Ops console" section —
worth reading before touching this area, since it also documents an honest
limitation: the two "Simulate" actions are a from-scratch simplified
reconstruction, not a restoration of the original modal's exact business
logic (self-referral/KYC blocking, a structured pending-commission ledger) —
that logic was deleted before this page existed and was never in version
control, so it couldn't be recovered byte-for-byte. Everything else (wallet
adjustment, settings, referral toggle, logs) reuses the *exact* localStorage
keys and formats `app-state.js` already reads, confirmed via grep before
writing a line of the new JS.

Verified via HTTP (full login flow: wrong password rejected, correct
password grants access, unauthenticated dashboard access redirects,
`npm run build` succeeds with `admin.js` as its own chunk). **Not verified**:
actual button/localStorage interactivity in a real browser — flagged this
explicitly to the user, same as every other JS-heavy change this session
that couldn't be visually confirmed.

---

## 2026-07-15 (10) — Removed bottom-nav's pt-3 and safe-area pb, reconfirmed the view-cache gotcha

User pasted two compiled CSS rules straight from DevTools (`.pt-3` and
`.pb-[calc(env(safe-area-inset-bottom,16px)+8px)]`) and asked to remove them.
Both only existed on `#bottom-nav` itself
(`resources/views/components/bottom-nav.blade.php` line 1) — removed both
classes from that one element, left `pt-3` usages elsewhere in the app alone
since the ask was about this specific element, not the utility globally.

Hit the **same stale-view-cache issue from entry (4) again**: after editing
and rebuilding, the safe-area class was still present in the compiled CSS.
Cause: `resources/css/app.css`'s `@source '../../storage/framework/views/*.php'`
directive means Tailwind's content scanner also reads Laravel's *compiled*
Blade cache, not just the source `.blade.php` files — and that cache still
had the old class string. `php artisan view:clear` before `npm run build`
fixed it (confirmed: 0 occurrences in the freshly compiled CSS after).
**This will keep happening on any future class-removal edit** (additions are
usually fine since new classes just get added regardless of cache staleness;
it's specifically *removing* a class that requires a fresh view cache to
verify) — worth just always running `view:clear` before rebuilding when a
change should make a utility class disappear, not only when investigating a
mystery bug.

Note: removing the safe-area-inset padding means the nav bar's content now
sits flush with the bottom edge on devices with a home-indicator gesture bar
(iPhone X+) — that's what was asked for, but worth knowing if it turns out
the icons/labels feel cramped against the edge on those devices.

---

## 2026-07-15 (9) — Removed the Admin Settings debug panel entirely

User asked to remove Admin Settings from the Profile page. It was a hidden
dev/testing panel (manual wallet-balance adjustment, simulate daily
commission release, simulate a friend's full referral flow, view/clear
referral & commission logs) reachable via a plain list-item button in
Profile settings, wired to `window.openAdminSettings()`.

Removed more than just the button: this is wallet/referral-manipulation
tooling sitting in a banking-portal app — even unlinked, it would still have
been reachable via page inspection or the browser console
(`window.openAdminSettings()` doesn't care whether a button points at it).
Checked first that nothing else in the app depends on it (`grep` across
`app/` and `resources/` for every admin-prefixed function name — zero hits
outside the modal itself), then removed:
- The "Admin Settings" list item in `app/Modules/Profile/Views/profile.blade.php`.
- The `<x-admin-settings-modal />` include and the component file
  `resources/views/components/admin-settings-modal.blade.php`.
- The whole admin-panel block in `resources/js/modules/app-state.js`
  (`openAdminSettings` through `simulateFriendReferralFlow`) — left
  `window.checkAndProcessReferral` alone right after it, since that's real
  app logic (processes actual referral conversions, called from
  `auth.js`'s `finalizeLogin`), not admin tooling.

One residual effect worth knowing: `checkAndProcessReferral` reads
`localStorage['gullakpe_admin_referral_enabled']` to decide whether the
referral program is active. With the admin panel gone, nothing ever sets
that key, so it defaults to enabled (`!== 'false'` short-circuits true) —
correct/expected behavior for a shipped app, not a bug, but if a real
referral-program on/off switch is ever wanted, it needs a real settings
entry now, not this leftover flag.

Rebuilt — JS bundle shrank ~200KB → ~190KB confirming the dead code is
actually gone, not just unreachable. Cleared the compiled view cache
afterward (same as MEMORY.md's entry (4) noted, view cache can serve stale
output after a Blade edit).

---

## 2026-07-15 (8) — App-wide "feels like a bank, not a game" motion/glow pass

User said the whole app (not one component) reads as "cartoon" and asked for
a professional-banking-portal feel across every page, with animation kept
but made smooth. Confirmed scope first: keep the existing teal/mint brand
and layout, refine execution only; apply in one pass across all pages rather
than one page at a time.

Given the size (7 pages, thousands of lines), fixed this at the **shared
design-token level in `resources/css/app.css`** rather than touching each
page's markup — every page already draws from the same custom classes, so
fixing the source cascades everywhere at once instead of requiring 7 separate
edits with 7 chances to miss one:

- **Bounce/spring easing, eliminated app-wide.** Found and replaced the only
  3 occurrences of overshoot cubic-beziers (`cubic-bezier(0.175, 0.885, 0.32, 1.275)`
  and `(0.34, 1.56, 0.64, 1)` — values above 1 in the curve are the literal
  definition of a springy overshoot) with a clean `cubic-bezier(0.22, 1, 0.36, 1)`
  (ease-out-quint): the "slide to invest" success-track entrance, the generic
  `.popup-card` modal entrance (used by every popup across the app), and the
  checkmark success icon. Also stripped a rotate+overshoot wobble
  (`scale(1.2) rotate(10deg)` mid-keyframe) out of `checkmark-morph` itself —
  the curve wasn't the only springy part, the keyframe was too.
- **Gold "VIP" glow halos toned down**: `.gold-glow` (Home's featured plan
  card) and `.gold-border-glow` (Explore's VIP plan card) were a bright
  `0 0 20px` gold blur — read as a lottery/rewards badge. Replaced with a
  restrained neutral elevation shadow + a quiet gold hairline border, so
  "premium" still reads without the carnival glow.
- **Infinite shine sweep, made interaction-triggered**: `.shimmer-effect`
  (Home's featured card) ran a light-streak animation on a 3-second loop
  forever. Motion should signal state, not run perpetually with nothing
  happening — changed to fire once on hover instead
  (`animation-play-state: paused` by default, `running` on `:hover`).
- **Left alone, deliberately**: `.red-glow-pulse`/`.teal-glow-pulse` (contextual
  error/valid state indicators, not idle decoration), the Material-style tap
  ripple (`initPremiumInteractions()` — touch-triggered feedback, not idle,
  and a broadly-accepted pattern), the `live-pulse` red dot (a real "live data"
  indicator, matches "motion conveys state"), and border-radius scale (audited
  the full range used across every view — 12 to 28px, progressively scaled by
  element size, not the wildly-inconsistent "bubble" look that would actually
  need fixing).

Verified via the compiled build output that zero overshoot easing curves
remain anywhere in `app.css`. Rebuilt and confirmed the live page serves the
new build.

**Not done, and worth knowing**: this pass fixed *motion and glow* system-wide
since those were the clearest, most systemic "cartoon" tells findable from
code alone. It did not touch typography, color saturation elsewhere, spacing
rhythm, or component layout on individual pages — if those still feel off
after this, that needs to be pointed at specifically (a screenshot, like the
login-popup and bottom-nav fixes both needed, gets to the real issue in one
pass instead of several rounds of guessing).

---

## 2026-07-15 (7) — Fixed nav-glass bleed-through (`make-interfaces-feel-better`)

User sent an actual screenshot this time (bottom nav rendered with Hindi
labels) showing the real bug: the `.nav-glass` translucent background
(`rgba(255,255,255,0.85)` + `blur(20px)`) was letting the scrolled page
content behind the fixed nav bar show through strongly enough to clash with
the icons/labels — read as broken, not as a deliberate frosted-glass surface.
Per the skill's "Surfaces" guidance (shadows over borders/translucency for
elements that sit over varied backgrounds), pushed the background to
near-opaque (`0.97`) with `blur(24px) saturate(180%)` for a still-premium
frosted feel without the legibility risk, and replaced the flat white
`border-top` with a shadow-based top hairline (`0 -1px rgba(0,0,0,0.06)`),
since a solid light border also reads inconsistently against varied content.
`resources/css/app.css`'s `.nav-glass` rule only — no Blade changes needed
this time. Rebuilt and verified the compiled rule.

---

## 2026-07-15 (6) — Bottom nav polish pass (`impeccable polish`)

User said the bottom nav "isn't professional." No screenshot this time, so
rather than guess at subjective redesign choices, did a code-level polish
pass on `resources/views/components/bottom-nav.blade.php` for concrete,
verifiable issues only — read the active-state logic in `navigation.js`
first (two near-duplicate blocks, both toggle `text-slate-400`/
`text-[#0A5C66]`, `font-medium`/`font-bold`, and `.nav-active-glow`'s
`opacity-0` — this predates the port, left as-is, not in scope) to make sure
nothing here would break it.

Fixed:
- **Icon family inconsistency**: every nav icon was `fa-solid` except Profile,
  which was `fa-regular fa-user` — a real mismatch, not a taste call (`fa-user`
  has both styles in Font Awesome Free; `fa-house`/`fa-chart-pie`/`fa-gift` do
  not, so this was fixed by matching Profile to solid, not by attempting an
  outline↔solid active-state swap across all icons, which would have risked
  rendering blank icons for the ones without a free regular variant).
- **Missing keyboard focus states**: none of the 5 nav buttons had a
  `focus-visible` treatment, so keyboard users got only the browser's default
  outline (inconsistent with the teal brand). Added
  `focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 focus-visible:ring-offset-2`
  to all of them (mint `#3FEA8A` ring on the dark Explore button specifically,
  since teal wouldn't show against its own gradient background).
- **Redundant/incomplete inline `style=` on the rewards notification badge**:
  it duplicated properties already covered by Tailwind classes (leftover
  defensive styling, likely from before the cascade-layer fix in memory entry
  (4)) *and* carried 4 properties with no Tailwind equivalent
  (`font-size`, `font-family`, `pointer-events`, `z-index`) that would have
  been silently lost if the inline style were just deleted. Moved those four
  into explicit utility classes (`text-[10px] font-poppins pointer-events-none
  z-50`) first, then removed the inline style — same visual result, no
  duplication.

Rebuilt and verified via HTTP that the live page has `fa-solid fa-user` (not
`fa-regular`) and the new focus-visible classes. Didn't touch the floating
Explore button's raised-circle treatment, the glow/shadow styling, or spacing
— nothing concrete pointed at those being wrong, and guessing at a redesign
without seeing what specifically reads as "unprofessional" risks polishing
the wrong thing. If something *specific* still looks off, a screenshot (like
the login-popup one) will get there faster than another blind pass.

---

## 2026-07-15 (5) — Systematic CSS/scrolling audit (via `impeccable` skill)

User reported an ongoing scrolling problem plus "lots and lots of CSS issues"
and asked for a systematic per-page pass, not more one-off patches. Ran an
audit-style sweep across all 7 module views + shared components using the
`impeccable` skill's technical-audit framework, on top of the migration
context already known from this session:

- **Confirmed the 2026-07-15 (4) cascade-layer fix is comprehensive**: grepped
  for any remaining inline `<style>` tags across every Blade view — none
  found, meaning all custom CSS is consolidated in `app.css`'s
  `@layer components` block. That fix wasn't just for the login popup; it
  applies to every `absolute`/`overflow`/`position` utility class anywhere in
  the app that also happens to get an `interactive-element`-family class from
  `initPremiumInteractions()`'s ripple effect — which is most buttons/cards in
  the app. This is almost certainly what "lots and lots of CSS issues" was
  actually one root cause manifesting many places, not many separate bugs.
- **Traced the Explore tab's search-modal scroll lock**
  (`window.lockExploreScroll` in `explore.blade.php`, ~line 1307): toggles
  Tailwind's `overflow-hidden` utility on both `document.body` and
  `#tab-explore`. Verified this no longer conflicts with anything now that
  custom CSS is properly layered — `body`'s custom rule only touches
  `position`/`top`/`padding-bottom` (see below), not `overflow`.
- **Flagged but did not change**: `resources/css/app.css`'s
  `body { position: relative !important; top: 0px !important; padding-bottom: 95px !important; }`.
  `!important` beats non-`!important` inline styles regardless of cascade
  layer — so if any *future* JS tries a position/top-based scroll-lock
  pattern on body (common trick: `body.style.position='fixed'; body.style.top='-Ypx'`),
  these `!important` rules would silently defeat it. Nothing in the current
  JS does this (checked all of `resources/js/modules/*.js` and every Blade
  view), so left as-is rather than changing behavior with no evidence of
  present breakage — but this is the first place to look if a *future*
  modal's scroll-lock doesn't work.
- **Checked z-index stacking** across all views (values used: 0, 10, 20, 25,
  30, 40, 50, 100, 110, 200, 9999, 10000) — the ordering is internally
  consistent (global-loader at 10000 sits above all modals at 9999, which sit
  above the explore-search-modal at 200, above nav elements at 100-110).
  Left as-is; these are inherited verbatim from the original design, and
  there's no evidence of an actual stacking conflict, so no reason to
  restructure into an abstract scale (would risk introducing a *new* bug to
  fix a hypothetical one).

Rebuilt and reverified after the sweep — no further changes were needed
beyond confirming the (4) fix's scope. If scrolling issues persist after a
hard-refresh past this point, it's a different, not-yet-diagnosed bug and
needs a screenshot/repro to pin down (same as the login-popup bug did) —
static analysis has been exhausted for the cascade-layer bug class.

---

## 2026-07-15 (4) — Fixed real visual bug: custom CSS silently beat Tailwind utilities

User sent a screenshot: the login overlay's back/close buttons were stacked
vertically and centered, instead of pinned to the card's top-left/top-right —
i.e. `position: absolute` (and `left-4`/`right-4`/`top-*`) appeared to have no
effect at all, so the buttons fell back to normal flex-column flow.

Root cause: `resources/css/app.css` has `@import "tailwindcss";` followed by
a `@theme {}` block, then ~1200 lines of hand-written CSS (`.interactive-element`,
`.premium-card`, etc. — all inherited verbatim from the original prototype's
`<style>` block). That hand-written CSS was **unlayered** plain CSS, while
Tailwind v4 generates all of its own output inside named CSS Cascade Layers
(`@layer theme, base, components, utilities`). Per the CSS Cascade Layers
spec, unlayered rules *always* beat layered rules at equal specificity,
regardless of source order. `initPremiumInteractions()` (in
`animations2.js`) adds an `interactive-element` class to buttons for the
ripple effect, and that class's `position: relative` — being unlayered — was
silently overriding the buttons' own `absolute` utility class on every button
in the app, not just this one; the login overlay was just where it was
visually obvious enough to notice.

This did not exist in the original prototype because Tailwind's CDN/Play
build doesn't organize its generated output into cascade layers the same
way, so ordinary cascade-order rules applied there instead.

Fix: wrapped everything below the `@theme` block in `@layer components { ... }`
(one sed-inserted opening line + closing brace at EOF — no content changes).
Verified in the compiled output that `.interactive-element` now lands inside
`@layer components`, which is declared *before* `@layer utilities`, so
utilities correctly win now. Confirmed layer order in build output:
`properties, theme, base, components, utilities`.

**If new hand-written CSS is ever added to `app.css` going forward, it must
go inside `@layer components { ... }` (or `@layer base`, as appropriate) —
never as bare unlayered CSS — or it will silently outrank Tailwind utility
classes again.** Documented in DESIGN.md too.

Also fixed the specific reported symptom itself in
`app/Modules/Auth/Views/auth-overlay.blade.php`: switched both header buttons
from a fixed `top-4.5` to `top-1/2 -translate-y-1/2`, which is a more robust
vertical-centering pattern regardless of this class of bug.

Also cleared a stale `storage/framework/views/*.php` compiled-view cache
while investigating (`php artisan view:clear`) — worth trying first if a
Blade edit ever doesn't seem to take effect.

---

## 2026-07-15 (3) — Post-port JS bugs: shared state must live on `window`

User hit these live in the browser after the port: `activePhoneNumber is not
defined`, then `updateMaskedPhones is not defined`, and the global loader
never disappearing (page stuck "loading"). Root cause, same shape each time:
the original monolith's classic `<script>` tags all shared one global lexical
scope, so a bare `let`/`const`/`function` in one `<script>` was silently
visible from a *later* `<script>` tag with no `window.` prefix needed. Once
split into separate ES modules (`resources/js/modules/*.js`), each file gets
its **own** module scope — those bare cross-file references throw
`ReferenceError` instead.

Found and fixed 4 instances by systematically diffing "where is X declared"
vs. "everywhere X is used" across the whole original file for every top-level
`let`/`const`/`function` in the six shared JS files (not just the ones called
from inline `onclick=` HTML, which is as far as the first pass checked):
- `activePhoneNumber` (`let` in `auth.js`, read/written from `navigation.js`)
  → moved the single declaration to `window.activePhoneNumber` in
  `app-state.js` (loads first), removed the shadowing `let` in `auth.js`.
- `updateMaskedPhones` (`function` in `auth.js`, called from `navigation.js`)
  → added `window.updateMaskedPhones = updateMaskedPhones;`.
- `GlobalLoader` and `SkeletonLoader` (`const {...}` objects in
  `global-loader.js`, `.hide()`/`.show()` called from both `navigation.js`
  and `auth.js` in ~10 places) → changed both to `window.GlobalLoader = {...}`
  / `window.SkeletonLoader = {...}`. **This was almost certainly the actual
  cause of the loader never hiding** — every code path that would call
  `GlobalLoader.hide()` was throwing first, so execution stopped before it ran.

Also reverted the i18n `loadPath` fix from the previous entry: changing it to
an absolute `/lang/{{lng}}.json` broke it again, since that resolves from the
domain root, not the app's mount path (`/gullakpe/gullakpe-laravel/`). Correct
form is a bare-relative `lang/{{lng}}.json`, which resolves against the
current page URL and works at any mount depth (root vhost or nested XAMPP path).

**If another `ReferenceError` for a plain identifier turns up**: grep the
original `../index.php` for every occurrence of that name, and check whether
any occurrence falls outside the source line-range of whichever
`resources/js/modules/*.js` file now contains its declaration (ranges are
documented in this same commit's diff / the module files' own header
comments). If so, expose it via `window.name = name` in the declaring file
rather than chasing it file-by-file.

---

## 2026-07-15 (2) — Modular Laravel port completed (per implementation_plan.md)

Executed `../implementation_plan.md`'s conversion of the 12k-line static
prototype into this modular Laravel app. Scope confirmed with the user first:
English + Hindi only (not all 8 languages from the language modal),
localStorage kept as-is (no DB models this pass), Blade + client-JS only (no
API backend), and kept the already-installed Laravel 12 / Tailwind v4 rather
than downgrading to the plan's Laravel 11 / Tailwind v3.

- **HTML**: extracted each tab's markup verbatim (byte-exact `sed` line-range
  copies, boundaries confirmed via `grep` for each `id="tab-*"`) into
  `app/Modules/{Home,Explore,Portfolio,Rewards,Profile,PlanDetails}/Views/*.blade.php`
  and `app/Modules/Auth/Views/auth-overlay.blade.php`. Also pulled out
  `resources/views/components/{bottom-nav,language-modal,popups,
  admin-settings-modal,system-overlays}.blade.php` (the last two — admin
  settings modal, and the pull-to-refresh/splash-screen divs — weren't
  mentioned in the plan but are needed for the page to work; found them by
  reading around the boundary lines).
- **CSS**: the global stylesheet (`resources/css/app.css`) was already done
  by an earlier session. Finished the job by extracting the three remaining
  tab-scoped `<style>` blocks (home, rewards, plan-details — largest was 424
  lines in plan-details) into `app.css` too, and removed them from the Blade
  views.
- **JS**: split the shared/global `<script>` blocks into
  `resources/js/modules/{global-loader,app-state,navigation,auth,animations,
  animations2,i18n}.js`, imported in original order from `resources/js/app.js`.
  Deliberately did **not** try to split the JS by "concern" beyond that — the
  code has no section-comment boundaries for wallet/rewards/referral, so
  guessing at split points risked silently breaking cross-function references.
  Left every tab-specific inline `<script>` embedded in its own Blade view
  (they still execute as plain classic scripts at their DOM position, exactly
  like the original — zero behavior change, zero scoping risk).
  - Verified the codebase's own convention before trusting this split: every
    function called across files already uses `window.fn = ...`, confirmed by
    grepping all bare (non-`window.`) `onclick="fn("` handlers app-wide and
    checking each definition. Only `openAuth`/`closeAuth` were plain
    `function` declarations relied on elsewhere via `window.openAuth`/
    `window.closeAuth` — added explicit `window.openAuth = openAuth;` (and
    `closeAuth`) since ES module top-level declarations don't auto-attach to
    `window` the way classic `<script>` globals do.
  - Fixed i18next's `loadPath` from `'./{{lng}}.json'` (relative — broke once
    real routes like `/explore` existed, since it resolved against the
    current URL path) to `'/lang/{{lng}}.json'`.
- **i18n files**: copied `../en.json` / `../hi.json` into both `public/lang/`
  (what the client-side i18next fetch actually loads) and `resources/lang/`
  (matching the plan's architecture diagram, for future Laravel-native
  `__()` use — not wired up yet).
- **Routing**: each module has its own `Controllers/*Controller.php` +
  `routes.php` (`/`, `/explore`, `/portfolio`, `/rewards`, `/profile`,
  `/plan-details`), auto-registered by the existing `ModuleServiceProvider`.
  All six controllers return the same `resources/views/pages/dashboard.blade.php`,
  which `@include`s every module's view as siblings inside `layouts.app` —
  matching the original single-page-with-client-side-tab-switching structure
  (this is a deliberate SPA-shell pattern per the plan, not an oversight).
- **Found and fixed a second `.htaccess`/`index.php` bug while verifying**:
  static assets (`public/build/*`, `public/lang/*.json`) 404'd because the
  asset-passthrough rule (and my first PHP-based fix) both assumed
  `DOCUMENT_ROOT`/`REQUEST_URI` reflect this folder directly — they don't,
  since it's nested under `htdocs/gullakpe/`. Moved the static-file serving
  into `index.php` using `SCRIPT_NAME` to compute the real mount prefix
  (works at any nesting depth), and added an extension-based MIME map since
  `mime_content_type()` was serving `.js`/`.css` as `text/plain` (which
  browsers refuse to execute as an ES module / can ignore as a stylesheet).
  Full detail in [SECURITY.md](SECURITY.md).
- **Verified**: `npm run build` compiles cleanly (62 modules), `node --check`
  passes on every extracted JS file, `php -l` passes on every new PHP file,
  and all 6 routes plus `lang/*.json` and `build/assets/*` return 200 with
  correct content-types via the live XAMPP entry point — checked by HTTP
  request/response inspection (status codes, content markers, MIME types),
  not by opening a real browser (none was available in this environment).
  **Still needs a manual pass in an actual browser** before calling this
  done: tab switching, EN/HI toggle, auth overlay, animations/ripples,
  visual match against the original — per the plan's own verification
  checklist. Flag this to the user.

---

## 2026-07-15 (1)

- **Added dual front controller for XAMPP.** Created `index.php` and `.htaccess`
  in this directory's root (alongside `public/`) so the app can be served at
  `http://localhost/gullakpe/gullakpe-laravel/` directly, without pointing the
  Apache vhost/document root at `public/`. `index.php` just `require`s
  `public/index.php` (PHP's `__DIR__` resolves per source file, not per
  include-site, so `public/index.php`'s existing relative paths still work
  unchanged). Verified via PowerShell `Invoke-WebRequest`: root URL returns 200
  and renders the real Laravel welcome page.

- **Found and fixed a real security exposure caused by the above.** Making this
  directory web-root-accessible meant `.env`, `composer.json`,
  `vendor/autoload.php`, `storage/logs/laravel.log` etc. were directly
  downloadable (verified 200 before the fix). Added deny rules to `.htaccess`
  blocking dotfiles and the `app/ bootstrap/ config/ database/ resources/
  routes/ storage/ tests/ vendor/ node_modules/` directories plus known
  sensitive files. Re-verified: those paths now 403, app root still 200. Full
  detail and the re-verification snippet live in [SECURITY.md](SECURITY.md) —
  re-run it after any future edit to `.htaccess` or either `index.php`.

- **Added this documentation set** (AGENTS.md, DESIGN.md, INSTRUCTIONS.md,
  SECURITY.md, MEMORY.md) at the user's request, to be kept current as the
  project changes, per project structure and support requirements below.

- **Noted for future work (not yet started):** There is a large (~12k line)
  static HTML/JS prototype at `../index.php` (i.e. `gullakpe/index.php`,
  *outside* this Laravel project — the parent `gullakpe/` folder, not to be
  confused with `gullakpe-laravel/index.php` added above). It's the full
  UI/UX reference for the app: phone+OTP+MPIN auth flow, dashboard with
  wallet/goals/gold-SIP, global loader, pull-to-refresh. It uses `i18next`
  client-side with `../en.json` / `../hi.json` as translation dictionaries
  (flat maps keyed by literal English source text), toggled via
  `localStorage['gullak_lang']`. The user wants this ported into the Laravel
  app eventually, with English/Hindi support preserved, but the actual
  porting work has **not started** — the user will give specific instructions
  for what to build next. Full mechanism and a porting checklist are recorded
  in [DESIGN.md](DESIGN.md) so this doesn't need to be re-derived from the
  12k-line file each time. Current Laravel app is still the stock
  `welcome.blade.php` starter page with no real routes/modules built yet.
