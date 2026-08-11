# Flexible Plan Real-Time JavaScript Previews Design Document

**Date:** 2026-08-11  
**Topic:** Real-time JavaScript preview and calculation for Flexible Plans in Admin Form & Plan Details UI  

---

## 1. Goal & Context
In GullakPe, Flexible Plans allow users to select an investment amount within a range (`min_investment_amount` to `max_investment_amount`). Server-side, `PlanManagementController` pins `investment_amount = min_investment_amount` in Flexible mode and calculates `daily_profit` and `total_return`.

To give admins real-time preview feedback while creating/editing plans before saving, and to ensure customers see accurate returns while dragging the slider on the Plan Details page, real-time JavaScript calculations must drive all preview elements dynamically based strictly on the input data.

---

## 2. Detailed Technical Design

### A. Admin Plan Form (`app/Modules/Admin/Views/plans/form.blade.php`)

1. **Dynamic Effective Investment Base (`effectiveAmount`)**:
   - When `investment_mode === 'flexible'`: `effectiveAmount = num(min_investment_amount)`.
   - When `investment_mode === 'fixed'`: `effectiveAmount = num(investment_amount)`.

2. **Headline & Explore Card Real-Time Preview**:
   - `daily_profit` & `total_return` disabled inputs, as well as Explore Card preview (`#lp-daily`, `#lp-total`, `#lp-amount`), recalculate dynamically on `input` events for `min_investment_amount`, `max_investment_amount`, `growth_rate`, `term_days_calc`, and `investment_amount`.
   - Formula:
     - `total = effectiveAmount * (1 + (ratePct / 100) * (days / 365))`
     - `daily = (total - effectiveAmount) / days`

3. **Duration Options Rows (`#duration-rows`)**:
   - Each duration row's `duration-daily-i` and `duration-total-i` outputs recalculate in real time using `effectiveAmount` and that row's specific `growth_rate` & `duration_days`.

4. **Flexible Range Live Preview Card (`#range-preview`)**:
   - Updates min/max labels (`range-preview-min`, `range-preview-max`) as `min_investment_amount` and `max_investment_amount` are typed.
   - Displays real-time projected Daily Profit and Total Return badges at **Min Investment** and **Max Investment** for the plan's rate and term days.

5. **Mode Switcher Integration**:
   - When toggling between `Fixed` and `Flexible` via `setPlanMode(mode)`, trigger immediate recalculation of all outputs and previews.

---

### B. Customer Plan Details View (`app/Modules/PlanDetails/Views/plan-details.blade.php`)

1. **Slider Recalculation Engine**:
   - On `input` event of `#pd-amount-slider`, compute exact daily profit and total return dynamically using the currently active duration's `growth_rate` and `duration_days`.
   - Math:
     - `yearlyInvested = sliderVal * 12` (for monthly habit label when top-up/flexible) or `sliderVal`
     - `dailyProfit = sliderVal * (ratePct / 100) / 365`
     - `totalReturn = sliderVal * (1 + (ratePct / 100) * (days / 365))`
     - `totalProfit = totalReturn - sliderVal`

2. **Real-Time UI Updates**:
   - `#pd-metric-daily-profit` updates to display the calculated `dailyProfit` for the dragged slider amount.
   - `#pd-metric-total-profit` updates to display calculated `totalProfit`.
   - `#pd-calc-summary-amount`, `#pd-calc-invested`, `#pd-calc-profit`, `#pd-flex-return`, `#sticky-amount-display`, and `#sticky-return-display` update instantly without static hardcoded factors.
   - Duration pill switching updates the active `ratePct` and `days` context for the slider recalculation engine.

---

## 3. Verification Plan
1. **Admin Panel Verification**:
   - Open `/admin/plans/create` or edit plan form.
   - Toggle to Flexible mode, enter Min = ₹500, Max = ₹5000, Growth rate = 12%, Term = 365 days.
   - Verify `daily_profit` shows `₹0.16` (500 * 12% / 365) and `total_return` shows `₹560.00`.
   - Verify duration rows update dynamically to reflect ₹500 base.
   - Verify `#range-preview` displays live min & max returns.
2. **Plan Details Verification**:
   - Open flexible plan page (`/plan-details?slug=...`).
   - Drag slider between min and max.
   - Verify daily profit, total return, invested amount, and profit labels scale linearly according to exact plan duration rate and days.
3. **Automated Tests**:
   - Run `php artisan test` to confirm all existing backend & UI tests continue to pass.
