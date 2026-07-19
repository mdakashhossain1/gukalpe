<?php

namespace Tests\Feature;

use App\Mail\DailyReturnsMail;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Models\UserPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Coverage for plans:send-daily-returns-email - the daily "your investments
 * grew today" digest. Growth Plan's 1-Year duration is used as the
 * not-yet-fully-accrued case (real room left to grow) and Trust Builder's
 * 1-Day duration as the already-fully-accrued case (nothing left to grow,
 * matching UserPlan::currentHolding()'s capped-accrual math).
 */
class DailyReturnsEmailTest extends TestCase
{
    use RefreshDatabase;

    private function holdingFor(User $user, string $planType, string $durationLabel, int $daysAgoPurchased): UserPlan
    {
        $plan = Plan::where('plan_type', $planType)->with('durations')->firstOrFail();
        $duration = $plan->durations->firstWhere('label', $durationLabel);

        return UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_duration_id' => $duration->id,
            'invested_amount' => $plan->investment_amount,
            'daily_profit_val' => $duration->daily_profit,
            'duration_label' => $duration->label,
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays($daysAgoPurchased),
            'matures_at' => now()->addDays($duration->duration_days - $daysAgoPurchased),
        ]);
    }

    public function test_daily_returns_email_is_sent_for_a_holding_with_real_growth_left(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'real@example.com']);
        $this->holdingFor($user, 'growth', '1 Year', 3);

        Artisan::call('plans:send-daily-returns-email');

        Mail::assertQueued(DailyReturnsMail::class, function (DailyReturnsMail $mail) use ($user) {
            return $mail->user->is($user)
                && $mail->totalDailyReturn > 0
                && $mail->holdings[0]['title'] === 'Growth Plan';
        });
    }

    public function test_daily_returns_email_is_not_sent_for_a_purchase_made_today(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'real@example.com']);
        $this->holdingFor($user, 'growth', '1 Year', 0);

        Artisan::call('plans:send-daily-returns-email');

        Mail::assertNothingQueued();
    }

    public function test_daily_returns_email_is_not_sent_twice_the_same_day(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'real@example.com']);
        $this->holdingFor($user, 'growth', '1 Year', 3);

        Artisan::call('plans:send-daily-returns-email');
        Artisan::call('plans:send-daily-returns-email');

        Mail::assertQueued(DailyReturnsMail::class, 1);
    }

    public function test_daily_returns_email_skipped_for_phone_only_accounts_without_a_real_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['phone' => '9123456780', 'email' => '9123456780@phone.gullakpe.local']);
        $this->holdingFor($user, 'growth', '1 Year', 3);

        Artisan::call('plans:send-daily-returns-email');

        Mail::assertNothingQueued();
    }

    public function test_daily_returns_email_skipped_once_a_holding_is_fully_accrued(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'real@example.com']);
        // Trust Builder's 1-day duration accrues its entire total_return by
        // day 1 - by day 3 there is nothing left to grow.
        $this->holdingFor($user, 'trust_builder', '1 Day', 3);

        Artisan::call('plans:send-daily-returns-email');

        Mail::assertNothingQueued();
    }
}
