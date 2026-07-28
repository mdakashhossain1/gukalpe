# GullakPe plan.md — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement all business logic specified in `plan.md` — Fixed/Flexible plan type switcher in admin form, interest-rate presets, catalog-wide purchase limits (Out of Stock), profit-only wallet credit on maturity, withdrawal limits enforcement, and nightly scheduler hardening.

**Architecture:** The plan.md spec maps onto five areas: (1) Admin form UX improvements (plan type switcher, interest presets, purchase limits), (2) a database migration adding catalog-wide purchase limit columns, (3) a critical bug fix in `MaturePlanHoldings` (currently credits full `currentValue` = invested + profit; spec says only profit), (4) `AppSetting` defaults + withdrawal controller enforcement for daily withdrawal limits (₹5k/day, 3 requests/day, ₹300 min), and (5) nightly scheduler moved to 00:00 IST per spec. No new modules — all changes live inside the existing module/model/command structure.

**Tech Stack:** Laravel 12, PHP 8.2+, Tailwind v4 (Vite build), SQLite (local), Pest/PHPUnit

## Global Constraints

- Architecture: single-page shell — every controller returns `dashboard.blade.php`. Don't add standalone pages.
- `window.fn = fn` required for any JS called from Blade `onclick=""` or from another module.
- Tests live in `tests/` (Pest). Run `php artisan test` to verify.
- After each task run `php artisan migrate` (if migration added) and `php artisan test`.
- MEMORY.md gets a dated entry after each non-trivial task.

---

## Task 1: DB Migration — Catalog Purchase Limit Columns

**Files:**
- Create: `database/migrations/2026_07_28_200000_add_purchase_limit_to_plans_table.php`
- Modify: `app/Models/Plan.php`

**Interfaces:**
- Produces: `Plan::$max_purchases` (int|null), `Plan::$total_purchases_count` (int), `Plan::isOutOfStock(): bool`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/PlanPurchaseLimitTest.php
<?php
use App\Models\Plan;

it('reports out of stock when total_purchases_count equals max_purchases', function () {
    $plan = Plan::factory()->create(['max_purchases' => 2, 'total_purchases_count' => 2]);
    expect($plan->isOutOfStock())->toBeTrue();
});

it('is not out of stock when unlimited', function () {
    $plan = Plan::factory()->create(['max_purchases' => null, 'total_purchases_count' => 5]);
    expect($plan->isOutOfStock())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

```
php artisan test --filter PlanPurchaseLimitTest
```
Expected: FAIL with "Call to undefined method App\Models\Plan::isOutOfStock()"

- [ ] **Step 3: Create the migration**

```php
// database/migrations/2026_07_28_200000_add_purchase_limit_to_plans_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_purchases')->nullable()->after('cooldown_days');
            $table->unsignedInteger('total_purchases_count')->default(0)->after('max_purchases');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_purchases', 'total_purchases_count']);
        });
    }
};
```

- [ ] **Step 4: Update Plan model**

In `app/Models/Plan.php`, add to `$fillable`: `'max_purchases', 'total_purchases_count'`

Add to `$casts`: `'max_purchases' => 'integer', 'total_purchases_count' => 'integer'`

Add method after `isTopupPot()`:
```php
public function isOutOfStock(): bool
{
    return $this->max_purchases !== null
        && $this->total_purchases_count >= $this->max_purchases;
}
```

- [ ] **Step 5: Run migration + tests**

```
php artisan migrate
php artisan test --filter PlanPurchaseLimitTest
```
Expected: PASS

- [ ] **Step 6: Commit**

```
git add database/migrations/2026_07_28_200000_add_purchase_limit_to_plans_table.php app/Models/Plan.php tests/Feature/PlanPurchaseLimitTest.php
git commit -m "feat: add catalog-wide purchase limit columns to plans table"
```

---

## Task 2: Bug Fix — Profit-Only Wallet Credit on Maturity

**Files:**
- Modify: `app/Console/Commands/MaturePlanHoldings.php`

**The Bug:** Line 39: `$creditAmount = $holding->currentHolding()['currentValue'];`
currentValue = invested + profit. Spec says **only profit** is credited. Fix: use `accruedProfit`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/MaturePlanHoldingsTest.php
<?php
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;

it('credits only profit to wallet on maturity, not the invested principal', function () {
    $user = User::factory()->create(['phone' => '9876543210']);
    WalletBalance::firstOrCreate(['phone' => '9876543210'], ['balance' => 0]);
    $plan = Plan::factory()->create([
        'investment_amount' => 500, 'daily_profit' => 10,
        'total_return' => 800, 'auto_mature' => true, 'is_active' => true,
    ]);
    UserPlan::create([
        'user_id' => $user->id, 'plan_id' => $plan->id,
        'invested_amount' => 500, 'daily_profit_val' => 10, 'total_return' => null,
        'duration_label' => '3 Months', 'status' => UserPlan::STATUS_ACTIVE,
        'purchased_at' => now()->subDays(30), 'matures_at' => now()->subDay(),
    ]);
    $this->artisan('plans:mature-holdings');
    // 10/day * 30 days = 300 profit, NOT 800 (invested+profit)
    expect(WalletBalance::balanceFor('9876543210'))->toBe(300.0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```
php artisan test --filter MaturePlanHoldingsTest
```
Expected: FAIL — balance is 800.0 instead of 300.0

- [ ] **Step 3: Fix MaturePlanHoldings.php**

Replace:
```php
$creditAmount = $holding->currentHolding()['currentValue'];
WalletBalance::credit($holding->user->phone, $creditAmount);
$holding->update(['status' => UserPlan::STATUS_WITHDRAWN, 'withdrawn_at' => now()]);
UserNotification::notify(
    $holding->user, 'plan_matured', "{$holding->plan->title} matured",
    'Your investment of ₹'.number_format((float) $holding->invested_amount, 2)." in {$holding->plan->title} has matured. ₹".number_format($creditAmount, 2).' has been credited to your wallet.'
);
Log::info('Plan holding matured', [
    'user_plan_id' => $holding->id, 'user_id' => $holding->user_id,
    'plan_id' => $holding->plan_id, 'credited' => $creditAmount,
]);
```

With:
```php
$holdingData  = $holding->currentHolding();
$profitAmount = $holdingData['accruedProfit']; // ONLY profit, never principal

WalletBalance::credit($holding->user->phone, $profitAmount);

$holding->update(['status' => UserPlan::STATUS_WITHDRAWN, 'withdrawn_at' => now()]);

UserNotification::notify(
    $holding->user,
    'plan_matured',
    "{$holding->plan->title} matured — profit credited",
    "Your {$holding->plan->title} plan has matured! ₹".number_format($profitAmount, 2)
        .' profit credited to your wallet. (Invested ₹'
        .number_format((float) $holding->invested_amount, 2).' is non-refundable per plan terms.)'
);

Log::info('Plan holding matured', [
    'user_plan_id'    => $holding->id,
    'user_id'         => $holding->user_id,
    'plan_id'         => $holding->plan_id,
    'profit_credited' => $profitAmount,
    'invested_amount' => (float) $holding->invested_amount,
]);
```

- [ ] **Step 4: Run test to verify it passes**

```
php artisan test --filter MaturePlanHoldingsTest
```
Expected: PASS

- [ ] **Step 5: Commit**

```
git add app/Console/Commands/MaturePlanHoldings.php tests/Feature/MaturePlanHoldingsTest.php
git commit -m "fix: credit only profit (not principal) to wallet on plan maturity"
```

---

## Task 3: Enforce Catalog Purchase Limit in PlanPurchaseController

**Files:**
- Modify: `app/Modules/Plans/Controllers/PlanPurchaseController.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/PlanOutOfStockTest.php
<?php
use App\Models\Plan;
use App\Models\User;
use App\Models\WalletBalance;

it('blocks purchase when plan is out of stock', function () {
    $user = User::factory()->create(['phone' => '9000000001']);
    WalletBalance::credit('9000000001', 1000);
    $plan = Plan::factory()->create([
        'is_active' => true, 'investment_amount' => 100,
        'daily_profit' => 1, 'total_return' => 130,
        'max_purchases' => 5, 'total_purchases_count' => 5,
    ]);
    $this->actingAs($user)
        ->post(route('plans.purchase', $plan))
        ->assertRedirect()->assertSessionHasErrors('plan');
    expect(WalletBalance::balanceFor('9000000001'))->toBe(1000.0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```
php artisan test --filter PlanOutOfStockTest
```
Expected: FAIL

- [ ] **Step 3: Add guard and increment in PlanPurchaseController**

After the `isWithinSchedule()` check:
```php
if ($plan->isOutOfStock()) {
    return back()->withErrors(['plan' => 'This plan is currently out of stock.']);
}
```

After `UserPlan::create([...])` and the optional `PlanTopup::create(...)`:
```php
$plan->increment('total_purchases_count');
```

- [ ] **Step 4: Run tests**

```
php artisan test --filter PlanOutOfStockTest
php artisan test
```
Expected: all pass

- [ ] **Step 5: Commit**

```
git add app/Modules/Plans/Controllers/PlanPurchaseController.php tests/Feature/PlanOutOfStockTest.php
git commit -m "feat: block purchase when catalog purchase limit reached (Out of Stock)"
```

---

## Task 4: Withdrawal Limits Enforcement

**Files:**
- Modify: `app/Models/AppSetting.php`
- Modify: withdrawal submission controller (find with: `grep -r "WithdrawRequest::create" app/ --include="*.php" -l`)
- Modify: `app/Modules/Admin/Views/settings.blade.php`
- Modify: `app/Modules/Admin/Controllers/AdminController.php`

- [ ] **Step 1: Add AppSetting DEFAULTS**

```php
'withdrawal_min_amount'    => '300',
'withdrawal_daily_limit'   => '5000',
'withdrawal_max_per_day'   => '3',
```

- [ ] **Step 2: Write the failing test**

```php
// tests/Feature/WithdrawalLimitsTest.php
<?php
use App\Models\AppSetting;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;

beforeEach(function () {
    AppSetting::set('withdrawal_min_amount', '300');
    AppSetting::set('withdrawal_daily_limit', '5000');
    AppSetting::set('withdrawal_max_per_day', '3');
});

it('rejects withdrawal below minimum amount', function () {
    $user = User::factory()->create(['phone' => '9100000001']);
    WalletBalance::credit('9100000001', 1000);
    $this->actingAs($user)
        ->post(route('withdrawals.store'), ['amount' => 100, 'upi_id' => 'test@upi'])
        ->assertRedirect()->assertSessionHasErrors();
    expect(WithdrawRequest::where('user_id', $user->id)->count())->toBe(0);
});

it('rejects withdrawal exceeding daily limit', function () {
    $user = User::factory()->create(['phone' => '9100000002']);
    WalletBalance::credit('9100000002', 10000);
    WithdrawRequest::factory()->create(['user_id' => $user->id, 'amount' => 5000, 'created_at' => now()]);
    $this->actingAs($user)
        ->post(route('withdrawals.store'), ['amount' => 500, 'upi_id' => 'test@upi'])
        ->assertRedirect()->assertSessionHasErrors();
});

it('rejects a 4th withdrawal request in one day', function () {
    $user = User::factory()->create(['phone' => '9100000003']);
    WalletBalance::credit('9100000003', 10000);
    WithdrawRequest::factory()->count(3)->create(['user_id' => $user->id, 'created_at' => now()]);
    $this->actingAs($user)
        ->post(route('withdrawals.store'), ['amount' => 300, 'upi_id' => 'test@upi'])
        ->assertRedirect()->assertSessionHasErrors();
});
```

- [ ] **Step 3: Run test to verify it fails**

```
php artisan test --filter WithdrawalLimitsTest
```
Expected: FAIL

- [ ] **Step 4: Add limit checks before WithdrawRequest::create() in withdrawal handler**

```php
$settings = AppSetting::many([
    'withdrawal_min_amount'  => '300',
    'withdrawal_daily_limit' => '5000',
    'withdrawal_max_per_day' => '3',
]);
$minAmount  = (float) $settings['withdrawal_min_amount'];
$dailyLimit = (float) $settings['withdrawal_daily_limit'];
$maxPerDay  = (int)   $settings['withdrawal_max_per_day'];

if ($amount < $minAmount) {
    return back()->withErrors(['amount' => 'Minimum withdrawal is ₹'.number_format($minAmount, 0).'.']);
}

$todayTotal = WithdrawRequest::where('user_id', $user->id)
    ->whereDate('created_at', today())->sum('amount');
if (($todayTotal + $amount) > $dailyLimit) {
    $remaining = max(0, $dailyLimit - $todayTotal);
    return back()->withErrors([
        'amount' => 'Daily limit ₹'.number_format($dailyLimit, 0).'. Up to ₹'.number_format($remaining, 0).' more today.',
    ]);
}

$todayCount = WithdrawRequest::where('user_id', $user->id)
    ->whereDate('created_at', today())->count();
if ($todayCount >= $maxPerDay) {
    return back()->withErrors(['amount' => "Max {$maxPerDay} withdrawal requests per day reached."]);
}
```

- [ ] **Step 5: Add admin settings fields + controller save logic**

In `settings.blade.php` add a "Withdrawal Limits" card with three inputs (same style as existing cards).

In `AdminController::updateSettings()` validate and save:
```php
foreach (['withdrawal_min_amount', 'withdrawal_daily_limit', 'withdrawal_max_per_day'] as $key) {
    if ($request->filled($key)) AppSetting::set($key, $request->input($key));
}
```

- [ ] **Step 6: Run tests**

```
php artisan test --filter WithdrawalLimitsTest
php artisan test
```
Expected: all pass

- [ ] **Step 7: Commit**

```
git add app/Models/AppSetting.php app/Modules/Admin/Views/settings.blade.php app/Modules/Admin/Controllers/AdminController.php tests/Feature/WithdrawalLimitsTest.php
git commit -m "feat: withdrawal limits (min ₹300, ₹5k/day, 3 requests/day) enforced + admin settings"
```

---

## Task 5: Admin Form — Fixed/Flexible Switcher + Interest Rate Presets + Purchase Limit

**Files:**
- Modify: `app/Modules/Admin/Views/plans/form.blade.php`
- Modify: `app/Modules/Admin/Controllers/PlanManagementController.php`

**A — Plan Type Switcher:**
Add segment buttons (Fixed / Flexible) at top of form. Hidden `<input name="investment_mode">` tracks state. JS `setPlanMode(mode)` toggles visibility of `#fixed-investment-section` (contains `investment_amount`) vs `#flexible-investment-section` (contains min/max/allow_topups). Init from existing data on load.

**B — Interest Rate Presets:**
Row of pill-buttons above `growth_rate` input: 1%, 2%, 3%, 5%, 8%, 10%, 12%, 15%, 20%, Custom %. Clicking a value sets `growth_rate` and fires `input` event to trigger existing auto-calculator. Active button gets brand color highlight.

**C — Purchase Limit:**
In Marketing & availability section: `max_purchases` number input (nullable = unlimited) + read-only sales counter `total_purchases_count / max_purchases`. Add `'max_purchases' => ['nullable','integer','min:1']` to `PlanManagementController::validated()`.

- [ ] **Step 1: Wrap investment sections and add switcher HTML**

In `form.blade.php`, immediately after `@csrf`:

```html
{{-- Step 1: Plan Type --}}
<div class="mb-4 p-4 bg-[#F8FAFC] rounded-xl border border-[#E5E9EB]">
    <label class="block text-[12.5px] font-semibold text-[#334155] mb-2.5">Investment type</label>
    <div class="inline-flex rounded-xl border border-[#CBD5E1] overflow-hidden">
        <button type="button" id="btn-fixed" onclick="window.setPlanMode('fixed')"
            class="px-5 py-2.5 text-[13.5px] font-bold transition-colors">
            <i class="bi bi-lock-fill mr-1"></i>Fixed Plan
        </button>
        <button type="button" id="btn-flexible" onclick="window.setPlanMode('flexible')"
            class="px-5 py-2.5 text-[13.5px] font-bold transition-colors">
            <i class="bi bi-sliders mr-1"></i>Flexible Plan
        </button>
    </div>
    <input type="hidden" name="investment_mode" id="investment_mode"
        value="{{ (old('min_investment_amount', $plan->min_investment_amount) !== null) ? 'flexible' : 'fixed' }}">
    <p class="text-[11px] text-[#94A3B8] mt-2">
        <strong>Fixed:</strong> Single set amount (e.g. ₹199). &nbsp;
        <strong>Flexible:</strong> User selects from a slider range (e.g. ₹100–₹1,000).
    </p>
</div>
```

Wrap existing `investment_amount` div in `<div id="fixed-investment-section">`.
Wrap existing min/max/slider-preview/allow_topups grid in `<div id="flexible-investment-section">`.

- [ ] **Step 2: Add interest rate presets above growth_rate input**

Above `<input name="growth_rate">`:
```html
<div class="mb-2">
    <span class="block text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wide mb-1.5">Quick presets</span>
    <div class="flex flex-wrap gap-1.5">
        @foreach ([1, 2, 3, 5, 8, 10, 12, 15, 20] as $rp)
        <button type="button" class="rate-preset px-3 py-1 rounded-full border border-[#CBD5E1]
            text-[12px] font-bold text-[#334155] hover:border-[#0A5C66] hover:text-[#0A5C66] transition-all"
            data-rate="{{ $rp }}" onclick="window.applyRatePreset({{ $rp }})">{{ $rp }}%</button>
        @endforeach
        <button type="button" class="rate-preset px-3 py-1 rounded-full border border-[#CBD5E1]
            text-[12px] font-bold text-[#334155] hover:border-[#0A5C66] hover:text-[#0A5C66] transition-all"
            data-rate="custom" onclick="window.applyRatePreset(null)">Custom %</button>
    </div>
</div>
```

- [ ] **Step 3: Add Purchase Limit fields after is_active checkbox**

After the `is_active` label (around line 220):
```html
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3">
    <div>
        <label for="max_purchases" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">
            Max total purchases (catalog-wide, optional)
        </label>
        <input type="number" name="max_purchases" id="max_purchases" min="1" placeholder="Unlimited"
            value="{{ old('max_purchases', $plan->max_purchases) }}"
            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
        <p class="text-[11px] text-[#94A3B8] mt-1">Leave blank for unlimited. Blocks purchases when count is reached.</p>
        @error('max_purchases')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Sales count (read-only)</label>
        <div class="h-10 flex items-center px-3 rounded-lg bg-[#F8FAFC] border border-[#E5E9EB] text-[14px] font-bold text-[#0F172A]">
            {{ number_format($plan->total_purchases_count ?? 0) }}
            @if ($plan->max_purchases)
            <span class="text-[#94A3B8] font-normal ml-1">/ {{ number_format($plan->max_purchases) }}</span>
            @endif
        </div>
    </div>
</div>
```

- [ ] **Step 4: Add JS to existing script block**

```javascript
window.setPlanMode = function(mode) {
    document.getElementById('investment_mode').value = mode;
    const isFixed = mode === 'fixed';
    document.getElementById('fixed-investment-section').style.display = isFixed ? '' : 'none';
    document.getElementById('flexible-investment-section').style.display = isFixed ? 'none' : '';
    const on  = 'px-5 py-2.5 text-[13.5px] font-bold transition-colors bg-[#0A5C66] text-white';
    const off = 'px-5 py-2.5 text-[13.5px] font-bold transition-colors bg-white text-[#64748B]';
    document.getElementById('btn-fixed').className   = isFixed ? on : off;
    document.getElementById('btn-flexible').className = isFixed ? off : on;
};

window.applyRatePreset = function(value) {
    const rateInput = document.getElementById('growth_rate');
    if (value !== null) {
        rateInput.value = value;
        rateInput.dispatchEvent(new Event('input'));
    }
    document.querySelectorAll('.rate-preset').forEach(function(btn) {
        const active = value !== null ? btn.dataset.rate === String(value) : btn.dataset.rate === 'custom';
        btn.classList.toggle('bg-[#0A5C66]', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('border-[#0A5C66]', active);
        btn.classList.toggle('text-[#334155]', !active);
    });
};

document.addEventListener('DOMContentLoaded', function () {
    window.setPlanMode(document.getElementById('investment_mode').value);
});
```

- [ ] **Step 5: Add max_purchases to PlanManagementController validated()**

```php
'max_purchases' => ['nullable', 'integer', 'min:1'],
```

- [ ] **Step 6: Run Vite build to pick up new classes**

```
npm run build
```

- [ ] **Step 7: Manual browser verification**

Open Admin → Plans → Add Plan. Confirm:
- Fixed/Flexible switcher shows correct sections
- Rate preset buttons fill `growth_rate` and trigger auto-calc
- `max_purchases` saves correctly (edit a plan, verify it persists)

- [ ] **Step 8: Commit**

```
git add app/Modules/Admin/Views/plans/form.blade.php app/Modules/Admin/Controllers/PlanManagementController.php
git commit -m "feat: admin plan form — Fixed/Flexible switcher, interest presets, catalog purchase limit UI"
```

---

## Task 6: Scheduler — Move mature-holdings to 00:00 IST

**Files:**
- Modify: `bootstrap/app.php`

Per spec Section 15: "Every Day 12:00 AM". Currently `everyMinute()` (dev shortcut). Fix: `dailyAt('18:30')` = 00:00 IST.

- [ ] **Step 1: Update withSchedule closure**

```php
->withSchedule(function (Schedule $schedule): void {
    // Midnight IST (18:30 UTC) — auto-matures holdings, credits only profit.
    $schedule->command('plans:mature-holdings')->dailyAt('18:30');
    // 09:00 IST (03:30 UTC) — daily profit email digest.
    $schedule->command('plans:send-daily-returns-email')->dailyAt('03:30');
})
```

- [ ] **Step 2: Verify**

```
php artisan schedule:list
```
Expected: `plans:mature-holdings` → Daily at 18:30, `plans:send-daily-returns-email` → Daily at 03:30

- [ ] **Step 3: Commit**

```
git add bootstrap/app.php
git commit -m "fix: run mature-holdings at 00:00 IST (18:30 UTC) not everyMinute"
```

---

## Task 7: Update MEMORY.md

- [ ] **Step 1: Prepend entry**

```markdown
## 2026-07-28 — plan.md business logic: profit-only maturity, purchase limits, withdrawal limits, admin form UX

Per plan.md spec (Sections 1–42):
- **CRITICAL BUG FIX:** `MaturePlanHoldings` was crediting `currentValue` (invested + profit). Spec mandates ONLY profit. Fixed to credit `accruedProfit`; principal is never returned.
- **Catalog purchase limits:** Migration adds `max_purchases` (nullable) + `total_purchases_count` to `plans`. `Plan::isOutOfStock()` added. `PlanPurchaseController` guards purchase, increments counter on success. Admin form shows limit field + read-only sales counter.
- **Withdrawal limits:** Added `withdrawal_min_amount` (₹300), `withdrawal_daily_limit` (₹5,000), `withdrawal_max_per_day` (3) to `AppSetting::DEFAULTS`. Enforced in withdrawal handler before `WithdrawRequest::create()`. Admin settings page exposes all three.
- **Admin form UX:** Fixed/Flexible plan type segment switcher (JS toggled), interest rate quick presets (1–20% + Custom), catalog limit fields.
- **Scheduler:** `plans:mature-holdings` `everyMinute()` → `dailyAt('18:30')` (= 00:00 IST). `plans:send-daily-returns-email` → `dailyAt('03:30')` (= 09:00 IST).
- Full test suite green after all changes.
```

- [ ] **Step 2: Commit**

```
git add MEMORY.md
git commit -m "docs: MEMORY.md — plan.md implementation summary"
```

---

## Verification Plan

### Automated Tests
```bash
php artisan test
php artisan test --filter PlanPurchaseLimit
php artisan test --filter PlanOutOfStock
php artisan test --filter MaturePlanHoldings
php artisan test --filter WithdrawalLimits
```

### Manual Verification
1. **Admin form** — `/admin/plans/create`: switcher, presets, limit field all work.
2. **Out of Stock** — `max_purchases=1`, purchase once → success; again → error.
3. **Profit-only maturity** — Run `php artisan plans:mature-holdings` with matured holding. Wallet = profit only.
4. **Withdrawal limits** — ₹100 below min → error. ₹5001 over daily cap → error. 4th request → error.
5. **Scheduler** — `php artisan schedule:list` shows correct timings.
