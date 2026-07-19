<?php

namespace App\Modules\Plans\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanTopup;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PlanPurchaseController extends Controller
{
    /**
     * Replaces the fake purchase in resources/js/modules/animations.js
     * (a setTimeout(2-3.5s) + Math.random() < 0.9 dice roll) with a real,
     * synchronous transaction: check the real wallet_balances table
     * (the same one Deposits/Withdrawals use), debit it, and record a real
     * UserPlan row. No random failure - the only way this fails is a
     * real insufficient-balance check, which is deterministic and honest
     * rather than a coin flip.
     *
     * Redirect+flash rather than JSON: the slide-to-invest JS widget that
     * used to fetch() this endpoint was removed along with the rest of the
     * app's JS, so the only remaining caller is a plain <form method="POST">.
     *
     * Guard order matters: cheapest/most-general checks first (schedule,
     * unlock, purchase limit, cooldown) before the wallet debit, so a user
     * blocked for any of those reasons never has their balance touched.
     */
    public function purchase(Request $request, Plan $plan): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login')->withErrors(['plan' => 'Please log in to invest in a plan.']);
        }

        if (! $plan->is_active || ! $plan->isWithinSchedule()) {
            return back()->withErrors(['plan' => 'This plan is no longer available.']);
        }

        $user = Auth::user();
        if (! $user->phone) {
            return back()->withErrors(['plan' => 'Add a phone number to your account before investing.']);
        }

        if ($plan->unlock_enabled && $plan->requires_plan_id) {
            $hasUnlocked = UserPlan::where('user_id', $user->id)
                ->where('plan_id', $plan->requires_plan_id)
                ->exists();

            if (! $hasUnlocked) {
                return back()->with('open_unlock_popup', $plan->id);
            }
        }

        if ($plan->max_purchase_per_user !== null) {
            $purchaseCount = UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->count();
            if ($purchaseCount >= $plan->max_purchase_per_user) {
                return back()->withErrors(['plan' => 'You have already reached the purchase limit for this plan.']);
            }
        }

        if ($plan->cooldown_days) {
            $lastPurchase = UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)
                ->latest('purchased_at')->first();
            if ($lastPurchase && $lastPurchase->purchased_at->addDays($plan->cooldown_days)->isFuture()) {
                return back()->withErrors(['plan' => 'This plan is on cooldown for your account. Please try again later.']);
            }
        }

        // A top-up-enabled plan's SECOND (and later) contribution adds to
        // the one ongoing pot instead of starting a fresh, independent
        // purchase - see Plan::isTopupPot()/UserPlan::activePotFor(). Every
        // other plan (including a flexible-amount plan with top-ups off)
        // always takes the "fresh purchase" branch below, unchanged.
        $existingPot = $plan->isTopupPot() ? UserPlan::activePotFor($user, $plan) : null;

        if ($existingPot) {
            return $this->topUp($request, $plan, $existingPot);
        }

        $duration = $this->resolveDuration($request, $plan);
        if ($plan->durations->isNotEmpty() && ! $duration) {
            return back()->withErrors(['plan' => 'Please select a duration for this plan.']);
        }

        if ($plan->isFlexibleAmount()) {
            $amount = $this->resolveFlexibleAmount($request, $plan);
            if ($amount === null) {
                return back()->withErrors([
                    'plan' => 'Enter an investment amount between ₹'.number_format((float) $plan->min_investment_amount, 0)
                        .' and ₹'.number_format((float) $plan->max_investment_amount, 0).'.',
                ]);
            }
        } else {
            $amount = (float) $plan->investment_amount;
        }

        // Proportional accrual (amount x duration's growth_rate) only for
        // flexible-amount purchases - the shared plan_durations row's own
        // daily_profit/total_return are calibrated for one specific
        // reference amount, not whatever the user just dragged the slider
        // to, so they can't be reused as-is. Fixed-amount purchases keep
        // using the duration's precomputed figures exactly as before.
        if ($plan->isFlexibleAmount() && $duration) {
            [$totalReturnAmount, $dailyProfit] = $this->proportionalReturn($amount, $duration);
        } else {
            $totalReturnAmount = $duration ? (float) $duration->total_return : (float) $plan->total_return;
            $dailyProfit = $duration ? (float) $duration->daily_profit : (float) $plan->daily_profit;
        }

        $durationLabel = $duration?->label ?? $plan->lock_duration;
        $maturesAt = $duration ? now()->addDays($duration->duration_days) : null;

        $available = WalletBalance::balanceFor($user->phone);

        if ($amount > $available) {
            return back()->with('insufficient_balance', ['needed' => $amount, 'available' => $available])
                ->withErrors([
                    'plan' => 'Insufficient wallet balance. Available: ₹'.number_format($available, 2).' · Needed: ₹'.number_format($amount, 2),
                ]);
        }

        $wallet = WalletBalance::debit($user->phone, $amount);

        $userPlan = UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_duration_id' => $duration?->id,
            'invested_amount' => $amount,
            'daily_profit_val' => $dailyProfit,
            // Only stamped for flexible-amount purchases - fixed-amount
            // purchases leave this null and keep resolving their cap from
            // planDuration/plan (see UserPlan::currentHolding()).
            'total_return' => $plan->isFlexibleAmount() ? $totalReturnAmount : null,
            'duration_label' => $durationLabel,
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now(),
            'matures_at' => $maturesAt,
        ]);

        // A complete contribution history from day one for top-up-enabled
        // plans, not just the top-ups that come after the first one.
        if ($plan->isTopupPot()) {
            PlanTopup::create(['user_plan_id' => $userPlan->id, 'amount' => $amount]);
        }

        Log::info('Plan purchased', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_title' => $plan->title,
            'amount' => $amount,
            'duration_label' => $durationLabel,
            'new_balance' => (float) $wallet->balance,
        ]);

        $this->creditReferralCommissionIfEligible($user, $userPlan, $amount);

        return redirect()->route('portfolio')
            ->with('success', "You've successfully invested ₹".number_format($amount, 2)." in {$plan->title}.")
            ->with('purchase_success', [
                'plan_type' => $plan->plan_type,
                'title' => $plan->title,
                'amount' => $amount,
                'duration_label' => $durationLabel,
                'total_return' => $totalReturnAmount,
                'matures_at' => $maturesAt,
            ]);
    }

    /**
     * Adds to the user's one ongoing pot for this plan instead of creating
     * a new UserPlan row - purchased_at/matures_at/plan_duration_id are
     * left untouched (shared single maturity date, per the user's explicit
     * "when the plan ends at a certain period, we get that much return"
     * requirement), only invested_amount/daily_profit_val/total_return move,
     * recomputed against the NEW cumulative total using the SAME duration
     * that was already locked in when the pot was first opened.
     */
    private function topUp(Request $request, Plan $plan, UserPlan $pot): RedirectResponse
    {
        $user = $pot->user;
        $max = (float) $plan->max_investment_amount;
        $currentTotal = (float) $pot->invested_amount;
        $raw = $request->input('amount');

        if (! is_numeric($raw) || (float) $raw <= 0) {
            return back()->withErrors(['plan' => 'Enter a valid amount to add.']);
        }

        $amount = (float) $raw;
        $newTotal = $currentTotal + $amount;

        if ($newTotal > $max) {
            $remaining = max(0, $max - $currentTotal);

            return back()->withErrors([
                'plan' => $remaining > 0
                    ? 'You can add up to ₹'.number_format($remaining, 2)." more to this plan (max ₹".number_format($max, 0).' total).'
                    : 'This plan has already reached its maximum investment of ₹'.number_format($max, 0).'.',
            ]);
        }

        $available = WalletBalance::balanceFor($user->phone);
        if ($amount > $available) {
            return back()->with('insufficient_balance', ['needed' => $amount, 'available' => $available])
                ->withErrors([
                    'plan' => 'Insufficient wallet balance. Available: ₹'.number_format($available, 2).' · Needed: ₹'.number_format($amount, 2),
                ]);
        }

        $duration = $pot->planDuration;
        [$newTotalReturn, $newDailyProfit] = $duration
            ? $this->proportionalReturn($newTotal, $duration)
            : [$newTotal, 0.0];

        $wallet = WalletBalance::debit($user->phone, $amount);

        $pot->update([
            'invested_amount' => $newTotal,
            'daily_profit_val' => $newDailyProfit,
            'total_return' => $newTotalReturn,
        ]);

        PlanTopup::create(['user_plan_id' => $pot->id, 'amount' => $amount]);

        Log::info('Plan topped up', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'user_plan_id' => $pot->id,
            'topup_amount' => $amount,
            'new_total' => $newTotal,
            'new_balance' => (float) $wallet->balance,
        ]);

        return redirect()->route('portfolio')
            ->with('success', "You've added ₹".number_format($amount, 2)." to your {$plan->title} investment. Total invested: ₹".number_format($newTotal, 2).'.')
            ->with('purchase_success', [
                'plan_type' => $plan->plan_type,
                'title' => $plan->title,
                'amount' => $newTotal,
                'is_topup' => true,
                'topup_amount' => $amount,
                'duration_label' => $pot->duration_label,
                'total_return' => $newTotalReturn,
                'matures_at' => $pot->matures_at,
            ]);
    }

    /**
     * @return array{0: float, 1: float} [totalReturn, dailyProfit] for
     *   $amount over $duration's full length at $duration's growth_rate.
     */
    private function proportionalReturn(float $amount, PlanDuration $duration): array
    {
        $years = $duration->duration_days / 365;
        $totalReturn = round($amount * (1 + ($duration->growth_rate / 100) * $years), 2);
        $dailyProfit = round(($totalReturn - $amount) / $duration->duration_days, 2);

        return [$totalReturn, $dailyProfit];
    }

    // null return means "invalid or out of range" - the caller turns that
    // into a single, plan-specific error message rather than duplicating
    // the range text here. Only used for the FIRST contribution that opens
    // a pot (or an ordinary one-time flexible purchase) - top-ups have
    // their own, looser validation in topUp() since only the cumulative
    // total needs to respect max, not each individual addition.
    private function resolveFlexibleAmount(Request $request, Plan $plan): ?float
    {
        $raw = $request->input('amount');
        if (! is_numeric($raw)) {
            return null;
        }

        $amount = (float) $raw;
        $min = (float) $plan->min_investment_amount;
        $max = (float) $plan->max_investment_amount;

        if ($amount < $min || $amount > $max) {
            return null;
        }

        return $amount;
    }

    private function resolveDuration(Request $request, Plan $plan): ?PlanDuration
    {
        if ($plan->durations->isEmpty()) {
            return null;
        }

        $durationId = $request->input('duration_id');
        if ($durationId) {
            $match = $plan->durations->firstWhere('id', (int) $durationId);
            if ($match) {
                return $match;
            }
        }

        return $plan->defaultDuration();
    }

    /**
     * Refer & Earn: a one-time commission to whoever referred $user, paid
     * only on their first-ever plan purchase (checked via the unique
     * user_plan_id on referral_commissions plus this exists() check, so a
     * retried request or a later purchase can never pay twice).
     */
    private function creditReferralCommissionIfEligible(User $user, UserPlan $userPlan, float $investedAmount): void
    {
        if (! $user->referred_by || AppSetting::get('referral_enabled', 'true') !== 'true') {
            return;
        }

        $hadEarlierPurchase = UserPlan::where('user_id', $user->id)
            ->where('id', '!=', $userPlan->id)
            ->exists();
        if ($hadEarlierPurchase) {
            return;
        }

        $referrer = User::find($user->referred_by);
        if (! $referrer || ! $referrer->phone) {
            return;
        }

        $percent = (float) AppSetting::get('commission_percent', '5');
        $amount = round($investedAmount * $percent / 100, 2);
        if ($amount <= 0) {
            return;
        }

        ReferralCommission::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'user_plan_id' => $userPlan->id,
            'amount' => $amount,
            'commission_percent' => $percent,
        ]);

        WalletBalance::credit($referrer->phone, $amount);

        AdminNotification::notify(
            'referral_commission',
            'Referral commission paid',
            "{$referrer->name} earned ₹".number_format($amount, 2)." for referring {$user->name}"
        );

        Log::info('Referral commission credited', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'user_plan_id' => $userPlan->id,
            'amount' => $amount,
            'commission_percent' => $percent,
        ]);
    }
}
