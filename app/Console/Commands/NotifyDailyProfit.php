<?php

namespace App\Console\Commands;

use App\Models\UserNotification;
use App\Models\UserPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyDailyProfit extends Command
{
    protected $signature = 'plans:notify-daily-profit';

    protected $description = 'Daily Profit Engine (plan.md Section 15/24/25): send each user an in-app notification of how much their active investments grew today.';

    /**
     * Mirrors SendDailyReturnsEmail's per-user digest + capped-accrual math,
     * but writes an in-app UserNotification instead of an email - so
     * phone-only users (the majority here, who have a synthetic email and are
     * skipped by the email digest) still get their "Today's profit updated"
     * notification. No money moves here: profit accrues on read in
     * UserPlan::currentHolding(); this job only informs the user and marks
     * each holding processed for the day so re-runs don't double-notify.
     */
    public function handle(): int
    {
        $holdings = UserPlan::dueForDailyProfitNotification()->with(['plan', 'user', 'planDuration'])->get();

        $byUser = $holdings->filter(fn (UserPlan $h) => $h->user && $h->plan)->groupBy('user_id');

        $notifiedCount = 0;

        foreach ($byUser as $userHoldings) {
            $user = $userHoldings->first()->user;

            $totalToday = 0.0;
            $planCount = 0;

            foreach ($userHoldings as $holding) {
                $plan = $holding->plan;
                $invested = (float) $holding->invested_amount;
                $dailyProfit = (float) $holding->daily_profit_val;
                // Same 3-level fallback as UserPlan::currentHolding(): a
                // per-holding snapshot beats the shared duration row, which
                // beats the plan's own base value.
                $totalReturn = (float) ($holding->total_return ?? $holding->planDuration?->total_return ?? $plan->total_return);
                $maxProfit = max(0.0, $totalReturn - $invested);

                $daysElapsed = max(0, (int) $holding->purchased_at->diffInDays(now()));
                $accruedToday = min($dailyProfit * $daysElapsed, $maxProfit);
                $accruedYesterday = min($dailyProfit * max(0, $daysElapsed - 1), $maxProfit);
                $todaysIncrement = $accruedToday - $accruedYesterday;

                if ($todaysIncrement > 0) {
                    $totalToday += $todaysIncrement;
                    $planCount++;
                }

                $holding->update(['last_daily_profit_notified_at' => now()->toDateString()]);
            }

            // Nothing grew today (every holding already fully accrued) - marked
            // processed above, but no point sending an empty notification.
            if ($totalToday <= 0) {
                continue;
            }

            $planLabel = $planCount === 1 ? 'plan' : 'plans';
            UserNotification::notify(
                $user,
                'daily_profit',
                "Today's profit updated",
                'Your investments grew by ₹'.number_format($totalToday, 2)." across {$planCount} active {$planLabel} today. Withdrawal unlocks once each plan matures."
            );

            Log::info('Daily profit notification sent', [
                'user_id' => $user->id,
                'plan_count' => $planCount,
                'total_today' => $totalToday,
            ]);

            $notifiedCount++;
        }

        $this->info("Sent daily profit notification to {$notifiedCount} user(s).");

        return self::SUCCESS;
    }
}
