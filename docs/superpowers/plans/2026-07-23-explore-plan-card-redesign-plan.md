# Explore Plan Card Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the plan card UI in `app/Modules/Explore/Views/explore.blade.php` to match the exact visual reference screenshot.

**Architecture:** Update the Blade template for `app/Modules/Explore/Views/explore.blade.php` to structure plan cards with top pastel badges, a left circular icon container, center title/subtitle + 3-column metrics grid with vertical dividers + trust indicators, and a right column with bold price and solid teal `Buy Now` CTA button.

**Tech Stack:** Laravel 12 Blade, Tailwind CSS v4, Bootstrap Icons (`bi-*`).

## Global Constraints
- Preserve single-page app architecture (`#tab-explore` wrapper).
- Preserve dynamic Blade variables (`$plan`, `$cp`, `$priceLabel`, `$priceCaption`, `$hasActiveFilters`, etc.).
- Maintain English and Hindi compatibility (use trans/i18n ready strings or existing dynamic model fields).

---

### Task 1: Redesign Plan Cards in Explore Module View

**Files:**
- Modify: `app/Modules/Explore/Views/explore.blade.php:100-178`

**Interfaces:**
- Consumes: `$plans` (Eloquent collection of `App\Models\Plan`), `$plan->toLegacyArray()` (`$cp`), `$plan->marketing_badge`, `$plan->marketing_badge_icon`, `$plan->marketingBadgeColorClasses()`.
- Produces: Updated HTML/Blade markup matching the reference screenshot design.

- [ ] **Step 1: Inspect current plan loop in `explore.blade.php`**
View lines 100 to 180 of `app/Modules/Explore/Views/explore.blade.php` to ensure correct variable references.

- [ ] **Step 2: Update the plan card markup in `explore.blade.php`**
Replace the plan card loop container with the updated card design:
- Top row: Left marketing badge (soft pastel bg `#FFF4E5`/`#ECFDF5`/`#F3E8FF`), Right category badge (soft pastel bg `#DCFCE7`/`#EFF6FF`/`#FEF3C7`).
- Main grid:
  - Left column: Circular icon pod `w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0 shadow-inner`.
  - Center column: Title, subtitle, 3-column stats with vertical borders (`border-r border-slate-200/80`), trust row (`🔒 End-to-End Encryption` & `🛡️ 100% Trusted & Secure`).
  - Right column: Large bold price (`text-[26px] sm:text-[30px] font-black text-slate-900`), `One-Time Investment` / `Flexible Amount` caption, and solid teal CTA button (`bg-[#0A5C66] text-white hover:bg-[#07474f] font-extrabold text-[13.5px] px-6 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 shadow-sm font-poppins`).

- [ ] **Step 3: Run PHP syntax check or test suite**
Run: `php -l app/Modules/Explore/Views/explore.blade.php`
Expected: `No syntax errors detected in app/Modules/Explore/Views/explore.blade.php`

- [ ] **Step 4: Commit changes**
Run: `git add app/Modules/Explore/Views/explore.blade.php`
Commit: `git commit -m "style: redesign explore plan cards to match exact reference screenshot"`
