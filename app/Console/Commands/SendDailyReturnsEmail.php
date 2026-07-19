<?php

namespace App\Console\Commands;

use App\Mail\DailyReturnsMail;
use App\Models\UserPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyReturnsEmail extends Command
{
    protected $signature = 'plans:send-daily-returns-email';

    protected $description = 'Email each user a daily summary of how much their active investments grew today.';

    /**
     * One email per user (not per holding) - a user with 3 active plans
     * gets a single digest listing all 3, not 3 separate emails. Skips any
     * holding that's already accrued its full total_return (nothing left
     * to grow today, matching the capped-accrual math in
     * UserPlan::currentHolding()) but still marks it as processed for
     * today so it isn't re-checked on every run.
     */
    public function handle(): int
    {
        $holdings = UserPlan::dueForDailyReturnEmail()->with(['plan', 'user', 'planDuration'])->get();

        $byUser = $holdings->filter(fn (UserPlan $h) => $h->user && $h->plan)->groupBy('user_id');

        $emailedCount = 0;

        foreach ($byUser as $userHoldings) {
            $user = $userHoldings->first()->user;

            $rows = [];
            $totalToday = 0.0;

            foreach ($userHoldings as $holding) {
                $plan = $holding->plan;
                $invested = (float) $holding->invested_amount;
                $dailyProfit = (float) $holding->daily_profit_val;
                // Same 3-level fallback as UserPlan::currentHolding(): a
                // per-holding snapshot (flexible-amount purchases) beats the
                // shared duration row, which beats the plan's own base value.
                $totalReturn = (float) ($holding->total_return ?? $holding->planDuration?->total_return ?? $plan->total_return);
                $maxProfit = max(0.0, $totalReturn - $invested);

                $daysElapsed = max(0, (int) $holding->purchased_at->diffInDays(now()));
                $accruedToday = min($dailyProfit * $daysElapsed, $maxProfit);
                $accruedYesterday = min($dailyProfit * max(0, $daysElapsed - 1), $maxProfit);
                $todaysIncrement = $accruedToday - $accruedYesterday;

                if ($todaysIncrement > 0) {
                    $rows[] = [
                        'title' => $plan->title,
                        'icon' => $plan->icon,
                        'amount' => $todaysIncrement,
                    ];
                    $totalToday += $todaysIncrement;
                }

                $holding->update(['last_daily_return_email_sent_at' => now()->toDateString()]);
            }

            if ($rows === [] || ! $user->hasRealEmail()) {
                continue;
            }

            $portfolioValue = UserPlan::holdingsFor($user)->sum('currentValue');

            Mail::to($user->email)->queue(new DailyReturnsMail($user, $rows, $totalToday, $portfolioValue));

            Log::info('Daily returns email queued', [
                'user_id' => $user->id,
                'plan_count' => count($rows),
                'total_today' => $totalToday,
            ]);

            $emailedCount++;
        }

        $this->info("Queued daily returns email for {$emailedCount} user(s).");

        return self::SUCCESS;
    }
}
