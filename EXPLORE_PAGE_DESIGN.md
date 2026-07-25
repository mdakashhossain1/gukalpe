# Explore Page — Design Breakdown

Source: `app/Modules/Explore/Views/explore.blade.php` (single-file view, ~475 lines,
no separate JS — all interactivity is CSS `:checked`/`peer-checked` driven). Controller:
`App\Modules\Explore\Controllers\ExploreController`. Route: `GET /explore`.

This file inventories every distinct UI element on the page and the exact classes/values
driving it, so improvement work has a concrete checklist instead of "make it nicer."

## Page shell

| Element | Values |
|---|---|
| Background | `#F8FAFC` (note: **not** the app-wide `bg` token `#F4F7F8` from DESIGN.md — a small, likely unintentional drift) |
| Bottom padding | `pb-28` (clears bottom nav) |
| Entrance | `animate-fade-in-up` on the whole tab |
| Scroll | `overflow-y-auto custom-scrollbar` |
| Desktop width | **None** — this page relies on the shared `.tab-content:not(#tab-home) { max-width: 860px }` rule from `app.css`/DESIGN.md's desktop layout section, not a page-specific treatment |

## 1. Sticky header

- `bg-white/80 backdrop-blur-md`, `sticky top-0 z-50`, hairline border `border-slate-200/40`, faint shadow `shadow-[0_4px_20px_rgba(10,92,102,0.02)]`.
- **Layout**: 3-column flex — circular back button (left) / centered title+subtitle / search+filter buttons (right).
  - Back button: 40px circle, `bg-slate-50` → `hover:bg-slate-100`, `hover:scale-105 active:scale-95`, `btn-ripple`.
  - Title: `text-[18px] font-extrabold text-[#0A5C66] font-poppins` — "Explore Goals".
  - Subtitle: `text-[11px] font-semibold text-slate-400` — "Build your future goals".
  - Search/Filter buttons: same 40px circle treatment as back button, filter button gets a small teal (`#3CCF91`) dot badge when `$hasActiveFilters` is true.
- **Filter chip row** (horizontal scroll, `hide-scrollbar`): "All Goals" + one chip per distinct Plan `badge` in the DB (data-driven, not hardcoded — see inline comment). Active chip: teal gradient `from-[#0A5C66] to-[#0E7481]` + inset highlight + outer glow shadow. Inactive: glassy `bg-white/40 backdrop-blur-md` with teal border, hover glow.
- **Active-filter pills** (conditional row, only when search/duration/amount filters are set): small pill badges (`bg-[#0A5C66]/8`), each with an icon + value + an "x" glyph that is **not actually wired as a remove button** — it's styled like a dismiss chip but the whole pill is one `<a>` that already strips that one filter via its `href`. Worth double-checking this reads clearly as tappable/removable.

## 2. Plan cards (the core repeating element)

Each plan renders as a white rounded card (`rounded-[20px] sm:rounded-[26px]`, `shadow-[0_4px_24px_rgba(0,0,0,0.03)]` → `hover:shadow-[0_8px_32px_rgba(0,0,0,0.07)]`). Internally:

1. **Top badges row**: marketing badge (color/icon selected by keyword match on `marketing_badge` text — `POPULAR`→orange star, `RETURN`→green graph-up, `STEADY`/`GROWTH`→purple trophy, fallback orange star) on the left; category badge (`STARTER`/`BEGINNER`/`PREMIUM`/fallback, each its own color pair) on the right. **This badge-coloring logic is a `@php` string-matching block duplicated inline in the view** — a candidate for extraction into a view composer/helper if more badge types get added.
2. **Title + subtitle**: `text-[15px] sm:text-[21px] font-extrabold text-[#0D1F3C] font-poppins`.
3. **Main content grid** (`grid-cols-[auto_1fr] md:grid-cols-[auto_1fr_auto]`):
   - **Icon pod**: 72px→112px circle, `bg-[#F2F7F8]`, either a real plan image or a Bootstrap Icon fallback, `group-hover:scale-105/110` on hover.
   - **Metrics row**: 3 columns separated by vertical dividers — Interest Rate (hidden on mobile, shown instead as the top-right badge), Total Return, Duration. Duration becomes a native `<select>` when a plan has multiple duration options (unstyled dropdown arrow via Bootstrap Icon overlay).
   - **Trust row**: two fixed, non-dynamic trust badges — "End-to-End Encryption" and "100% Trusted & Secure", both with the same teal icon treatment. These are static claims, not derived from any real security data.
   - **Price + Buy Now** (desktop only, `hidden md:flex`): right-aligned price + caption + solid teal CTA button with `btn-shimmer btn-ripple`.
4. **Mobile price/CTA row**: same price/button content repeated in a `md:hidden` block at the card's bottom, above a top border. (Two copies of the same price+CTA markup — one per breakpoint — rather than one repositioned element.)

## 3. Empty state

Centered card: soft teal icon circle (`bi-zoom-out`), bold headline "No Matching Goals", contextual body copy (search vs. filter vs. no-plans-at-all), and a "Reset All Filters" button shown only when a filter is actually active.

## 4. Search overlay (CSS-only sheet)

Triggered by `#explore-search-toggle` (hidden checkbox + `<label>`), shown via `peer-checked/search:flex`. Full-screen scrim (`bg-slate-900/60 backdrop-blur-md`) with a centered modal card (`max-w-lg`, `mt-16`). Real `<form method="GET">` — filters already active are preserved as hidden inputs so search composes with them rather than resetting them. Result count + "Clear" link shown once a query is active.

## 5. Filter overlay (CSS-only side sheet)

Triggered by `#explore-filter-toggle`, slides in as a left-anchored `w-[360px]` panel (not actually animated — it's a hard `hidden`→`flex` toggle, no transition/transform on the panel itself despite the "slides in" visual intent of a side sheet). Sections, each a labeled group of pill-radio buttons (`peer-checked` styling matching the header chips):

- **Category** (from real `badge` values)
- **Plan Duration** (from real durations present in the catalog)
- **Return Rate** (`min_growth`, real growth-rate thresholds)
- **Risk Level** (real `risk_level` values)
- **Sort By** — five fixed options: Lowest Investment, Highest Return, Newest, Ending Soon, Most Popular
- **Investment Range** — a real dual-thumb range: two overlapping native `<input type="range">` elements on one visual track, with a gradient-filled bar computed server-side from current min/max. No JS keeps the two thumbs from crossing past each other (a known native-range limitation) — worth checking that in-browser.

Footer: gradient "Apply Filters" submit button + a plain-text "Close" label.

## Type/element inventory (quick reference)

- **Buttons**: circular icon buttons (header), pill chips (filter/category), solid gradient CTA (Buy Now, Apply Filters), text-only links (Reset All, Close, Clear).
- **Cards**: plan card (primary), empty-state card, search modal card, filter side-sheet card.
- **Badges/pills**: marketing badge, category badge, active-filter pill, category/duration/rate/risk/sort filter pills, mobile rate badge.
- **Inputs**: text search, native duration `<select>`, radio-as-pill (category/duration/rate/risk/sort), dual `<input type="range">`.
- **Overlays**: search sheet (centered, scrim), filter sheet (left-anchored, scrim) — both pure-CSS via hidden checkbox + `peer-checked`, no JS.
- **Icons**: exclusively Bootstrap Icons (`bi bi-*`) on this page — no FontAwesome or Lucide, unlike other parts of the app per DESIGN.md.

## Things worth revisiting for improvement

1. **Background color drift**: `#F8FAFC` here vs. the documented app `bg` token `#F4F7F8` — likely should match.
2. **Duplicated badge-color logic**: the marketing/category badge color/icon `@php` blocks are inline per-card and would be simpler as a small helper/accessor on the `Plan` model or a view composer.
3. **Static trust badges**: "100% Trusted & Secure" / "End-to-End Encryption" are hardcoded marketing copy with no backing data — fine as-is but worth being intentional about, since everything else on this page is now real/DB-driven.
4. **Filter sheet has no motion**: unlike a typical side-sheet pattern, it snaps open/closed with no transform/transition, which stands out against the rest of the app's `ease-out-quint` one-shot entrance convention (see DESIGN.md's motion policy).
5. **Duplicated price/CTA markup**: desktop and mobile each render their own full copy of the price+button block rather than one repositioned element — fine functionally, but two places to keep in sync if pricing display ever changes.
6. **Icon system inconsistency**: this page is 100% Bootstrap Icons while DESIGN.md documents FontAwesome + Lucide as the app's icon stack — worth confirming which is actually the intended standard going forward.
7. **No page-specific desktop reflow**: per DESIGN.md's "Desktop layout system" entry, Explore was explicitly *not* given a bespoke desktop treatment like Home — it only gets the generic `max-width: 860px` centering. The plan-card grid could become a real multi-column layout at `md:`/`lg:` the way Home's Plans section did.
