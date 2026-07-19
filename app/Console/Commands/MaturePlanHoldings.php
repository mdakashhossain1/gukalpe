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

            $creditAmount = $holding->currentHolding()['currentValue'];

            WalletBalance::credit($holding->user->phone, $creditAmount);

            $holding->update([
                'status' => UserPlan::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);

            UserNotification::notify(
                $holding->user,
                'plan_matured',
                "{$holding->plan->title} matured",
                'Your investment of ₹'.number_format((float) $holding->invested_amount, 2)." in {$holding->plan->title} has matured. ₹".number_format($creditAmount, 2).' has been credited to your wallet.'
            );

            Log::info('Plan holding matured', [
                'user_plan_id' => $holding->id,
                'user_id' => $holding->user_id,
                'plan_id' => $holding->plan_id,
                'credited' => $creditAmount,
            ]);

            $maturedCount++;
        }

        $this->info("Matured {$maturedCount} holding(s).");

        return self::SUCCESS;
    }
}
