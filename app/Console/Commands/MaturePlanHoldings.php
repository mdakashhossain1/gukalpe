<?php

namespace App\Console\Commands;

use App\Models\UserNotification;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MaturePlanHoldings extends Command
{
    protected $signature = 'plans:mature-holdings';

    protected $description = 'Auto-credit wallet and mark withdrawn every active holding past its maturity date, for plans with auto_mature enabled.';

    /**
     * First scheduled job in this codebase (no app/Console/Commands or
     * ->withSchedule() existed before this feature) - drives Trust
     * Builder's "1 Day -> Withdrawal" promise and Growth Plan's fixed
     * maturity dates without any manual admin step, for any plan an admin
     * has left auto_mature = true (Phase 0/5). Plans with auto_mature =
     * false are deliberately left active past matures_at for a future
     * manual-mature admin action - not built yet, out of scope here.
     *
     * 2026-08-09: credits invested_amount + accruedProfit (the holding's
     * full matured value) - this deliberately REVERSES the original spec's
     * R1/R2 rule ("Investment Amount is NEVER returned. ONLY Profit
     * credited.", docs/superpowers/specs/2026-07-28-admin-plan-system-and-
     * engines-design.md). That rule matched the code up to this point but
     * didn't match what Plan Details' own "Maturity ₹219" figure had always
     * promised the user (principal + profit) - the site owner confirmed
     * "Maturity" should mean the user actually receives that full amount.
     * See MEMORY.md for the explicit confirmation this was intentional.
     */
    public function handle(): int
    {
        $holdings = UserPlan::matured()->with(['plan', 'user', 'planDuration'])
            ->whereHas('plan', fn ($q) => $q->where('auto_mature', true))
            ->get();

        $maturedCount = 0;

        foreach ($holdings as $holding) {
            if (! $holding->user || ! $holding->user->phone || ! $holding->plan) {
                continue;
            }

            $holdingData = $holding->currentHolding();
            $investedAmount = $holdingData['invested'];
            $profitAmount = $holdingData['accruedProfit'];
            $totalCredit = $investedAmount + $profitAmount;

            WalletBalance::credit($holding->user->phone, $totalCredit, 'plan_maturity_credit', [
                'plan' => $holding->plan->title,
                'user_plan_id' => $holding->id,
                'invested_amount' => $investedAmount,
                'profit_amount' => $profitAmount,
            ]);

            $holding->update([
                'status' => UserPlan::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);

            UserNotification::notify(
                $holding->user,
                'plan_matured',
                "{$holding->plan->title} matured — ₹".number_format($totalCredit, 2).' credited',
                "Your {$holding->plan->title} plan has matured! ₹".number_format($totalCredit, 2)
                    .' (₹'.number_format($investedAmount, 2).' investment + ₹'.number_format($profitAmount, 2)
                    .' profit) has been credited to your wallet.'
            );

            Log::info('Plan holding matured', [
                'user_plan_id' => $holding->id,
                'user_id' => $holding->user_id,
                'plan_id' => $holding->plan_id,
                'invested_amount' => $investedAmount,
                'profit_credited' => $profitAmount,
                'total_credited' => $totalCredit,
            ]);

            $maturedCount++;
        }

        $this->info("Matured {$maturedCount} holding(s).");

        return self::SUCCESS;
    }
}
