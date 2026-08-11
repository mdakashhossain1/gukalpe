# Flexible Plan Real-Time JavaScript Previews Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement real-time JavaScript preview & calculation for Flexible Plans in the Admin Plan Form and on the customer-facing Plan Details page.

**Architecture:** Update `app/Modules/Admin/Views/plans/form.blade.php` to calculate live returns based on `min_investment_amount` when in Flexible mode (and update duration rows + `#range-preview` return badges). Update `app/Modules/PlanDetails/Views/plan-details.blade.php` to calculate exact real-time daily profit and total return as the user moves the slider using the active duration's rate and term days.

**Tech Stack:** Laravel Blade, JavaScript (ES Module/vanilla JS matching codebase standard), Tailwind CSS v4, PHPUnit / Pest tests.

## Global Constraints
- PHP 8.2+, Laravel 12
- Vanilla JS matching codebase patterns
- EN/HI translation compliance where applicable

---

### Task 1: Admin Plan Form Real-Time JS Preview (`form.blade.php`)

**Files:**
- Modify: `app/Modules/Admin/Views/plans/form.blade.php`

- [ ] **Step 1: Update `recalcPlanLevel()` and `recalcRow()` to use dynamic base amount**

In `app/Modules/Admin/Views/plans/form.blade.php`:
Define `getEffectiveInvestmentAmount()`:
```javascript
function getEffectiveInvestmentAmount() {
    var modeInput = document.getElementById('investment_mode');
    var isFlexible = modeInput && modeInput.value === 'flexible';
    if (isFlexible) {
        var minEl = document.getElementById('min_investment_amount');
        return num(minEl) || 0;
    }
    return num(investmentEl) || 0;
}
```
Update `recalcPlanLevel()`:
```javascript
function recalcPlanLevel() {
    var amount = getEffectiveInvestmentAmount();
    var r = computeReturn(amount, num(rateEl), num(termEl));
    if (!r) return;
    fill(dailyEl, r.daily);
    fill(totalEl, r.total);
    updateLivePreview();
}
```
Update event listeners to include `min_investment_amount`, `max_investment_amount`, `slider_step`:
```javascript
[investmentEl, minInvestEl, maxInvestEl, sliderStepEl, rateEl, termEl].forEach(function (el) {
    if (el) el.addEventListener('input', recalcPlanLevel);
});
```

- [ ] **Step 2: Update duration row recalculations (`recalcRow`) to use dynamic base amount**

```javascript
function recalcRow() {
    var amount = getEffectiveInvestmentAmount();
    var r = computeReturn(amount, num(rRateEl), num(daysEl));
    if (!r) return;
    fill(rDailyEl, r.daily);
    fill(rTotalEl, r.total);
}
```
And listen to `minInvestEl` input to trigger `rowState.forEach(function (recalc) { recalc(); })`.

- [ ] **Step 3: Enhance `#range-preview` in Flexible section to display live real-time returns**

In `flexible-investment-section`'s `#range-preview`:
Update HTML to show return details for Min and Max:
```html
<div id="range-preview" class="sm:col-span-2 rounded-xl border border-[#E2E8F0] bg-white px-3.5 py-3" hidden>
    <p class="text-[10.5px] font-bold text-[#94A3B8] uppercase tracking-wide mb-2.5">Customer will see a slider like this on Plan Details</p>
    <div class="flex items-center gap-2.5">
        <span id="range-preview-min" class="text-[12.5px] font-black text-[#0F172A] whitespace-nowrap">₹0</span>
        <div class="relative flex-1 h-2 bg-slate-200 rounded-full">
            <div class="absolute inset-0 bg-[#0A5C66] rounded-full"></div>
            <div class="absolute top-1/2 left-0 -translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-white border-2 border-[#0A5C66] shadow"></div>
            <div class="absolute top-1/2 right-0 translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-white border-2 border-[#0A5C66] shadow"></div>
        </div>
        <span id="range-preview-max" class="text-[12.5px] font-black text-[#0F172A] whitespace-nowrap">₹0</span>
    </div>
    <div id="range-preview-returns" class="mt-2.5 flex items-center justify-between text-[11px] font-medium text-slate-600 border-t border-slate-100 pt-2">
        <span id="range-preview-min-return" class="text-[#0A5C66] font-bold">Min: Daily profit — Total return —</span>
        <span id="range-preview-max-return" class="text-[#0A5C66] font-bold">Max: Daily profit — Total return —</span>
    </div>
    <p id="range-preview-step" class="text-[10.5px] text-[#94A3B8] mt-1"></p>
</div>
```
In JS `updateRangePreview()`:
```javascript
function updateRangePreview() {
    var minVal = num(minInvestEl) || 0;
    var maxVal = num(maxInvestEl) || 0;
    var rate = num(rateEl) || 0;
    var days = num(termEl) || 365;

    var minR = computeReturn(minVal, rate, days);
    var maxR = computeReturn(maxVal, rate, days);

    if (minR) {
        document.getElementById('range-preview-min-return').textContent = 'Min ₹' + Math.round(minVal) + ': +₹' + minR.daily + '/day (Total ₹' + minR.total + ')';
    }
    if (maxR) {
        document.getElementById('range-preview-max-return').textContent = 'Max ₹' + Math.round(maxVal) + ': +₹' + maxR.daily + '/day (Total ₹' + maxR.total + ')';
    }
}
```

- [ ] **Step 4: Update `setPlanMode()` to trigger recalculations immediately on mode switch**

In `window.setPlanMode`:
```javascript
recalcPlanLevel();
rowState.forEach(function (recalc) { recalc(); });
updateRangePreview();
```

- [ ] **Step 5: Verify Admin Plan Form changes**
Run test suite to ensure form rendering and validation pass cleanly: `php artisan test --filter=Plan`

- [ ] **Step 6: Commit Task 1**
`git add app/Modules/Admin/Views/plans/form.blade.php; git commit -m "feat: real-time JS previews for flexible plans in admin form"`

---

### Task 2: Customer Plan Details Real-Time Slider Calculation (`plan-details.blade.php`)

**Files:**
- Modify: `app/Modules/PlanDetails/Views/plan-details.blade.php`

- [ ] **Step 1: Pass growth_rate and duration_days into slider calculation script**

In `app/Modules/PlanDetails/Views/plan-details.blade.php`:
Track the active duration row's `growth_rate` and `duration_days`. Attach `data-rate="{{ $defaultDuration->growth_rate }}"` and `data-days="{{ $defaultDuration->duration_days }}"` to duration pill buttons.

- [ ] **Step 2: Update slider `updateValues()` script for exact real-time daily profit and total return**

In `plan-details.blade.php`:
```javascript
function getActiveRateAndDays() {
    var activePill = document.querySelector('.dur-pill-btn.bg-\\[\\#0A5C66\\]');
    var rate = activePill ? parseFloat(activePill.dataset.rate) : parseFloat(container.dataset.growthRate || 0);
    var days = activePill ? parseInt(activePill.dataset.days, 10) : parseInt(container.dataset.termDays || 365, 10);
    return { rate: rate, days: days };
}

function updateValues() {
    if (!slider) return;
    var val = parseFloat(slider.value) || 0;
    var formatted = formatMoney(val);

    var info = getActiveRateAndDays();
    var ratePct = info.rate || 0;
    var days = info.days || 365;

    var totalReturn = val * (1 + (ratePct / 100) * (days / 365));
    var dailyProfit = (totalReturn - val) / days;
    var totalProfit = Math.max(0, totalReturn - val);

    if (amountDisplay) amountDisplay.textContent = formatted;
    if (hiddenInput) hiddenInput.value = val;
    if (summaryAmount) summaryAmount.textContent = formatted;
    if (stickyAmount) stickyAmount.textContent = formatted;
    if (stickyBtnAmount) stickyBtnAmount.textContent = formatted;

    var dailyProfitEl = document.getElementById('pd-metric-daily-profit');
    var totalProfitEl = document.getElementById('pd-metric-total-profit');
    if (dailyProfitEl) dailyProfitEl.textContent = '+₹' + dailyProfit.toFixed(2) + '/day';
    if (totalProfitEl) totalProfitEl.textContent = formatMoney(totalReturn);

    if (summaryInvested) summaryInvested.textContent = formatMoney(val * (days >= 30 ? Math.round(days / 30) : 1));
    if (summaryProfit) summaryProfit.textContent = formatMoney(totalProfit) + '+';
    if (summaryReturn) summaryReturn.textContent = formatMoney(totalReturn) + '+';
    if (stickyReturn) stickyReturn.textContent = formatMoney(totalReturn);
}
```

- [ ] **Step 3: Update duration pill click listener to trigger `updateValues()`**

When a duration pill is clicked in `selectDuration(button)`:
Call `updateValues()` so all metrics recalculate for the new rate and days.

- [ ] **Step 4: Verify Plan Details changes**
Run test suite: `php artisan test --filter=FlexibleAmountPlanTest`

- [ ] **Step 5: Commit Task 2**
`git add app/Modules/PlanDetails/Views/plan-details.blade.php; git commit -m "feat: real-time JS returns and daily profit calculation on flexible slider"`
