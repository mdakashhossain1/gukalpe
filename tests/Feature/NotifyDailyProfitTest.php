<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifyDailyProfitTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(): Plan
    {
        return Plan::create([
            'title' => 'Daily Profit Plan',
            'subtitle' => 'Test',
            'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank',
            'investment_amount' => 500,
            'daily_profit' => 10,
            'total_return' => 800,
            'growth_rate' => 10,
            'lock_duration' => '3 Months',
            'badge' => 'General',
            'auto_mature' => true,
            'is_active' => true,
        ]);
    }

    public function test_sends_in_app_daily_profit_notification_and_is_idempotent(): void
    {
        // Phone-only user (synthetic email) — the email digest skips these, the
        // in-app notification must not.
        $user = User::factory()->create([
            'phone' => '9876543210',
            'email' => '9876543210@phone.gullakpe.local',
        ]);
        $plan = $this->makePlan();

        UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'invested_amount' => 500,
            'daily_profit_val' => 10,
            'total_return' => null,
            'duration_label' => '3 Months',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(3),
            'matures_at' => now()->addDays(30),
        ]);

        $this->artisan('plans:notify-daily-profit')->assertSuccessful();

        $notes = UserNotification::where('user_id', $user->id)->where('type', 'daily_profit')->get();
        $this->assertCount(1, $notes);
        // Day 3 accrual (30) minus day 2 (20) = 10 grew today.
        $this->assertStringContainsString('10.00', $notes->first()->body);

        $this->assertEquals(
            now()->toDateString(),
            UserPlan::where('user_id', $user->id)->first()->last_daily_profit_notified_at->toDateString()
        );

        // Same-day re-run must not create a second notification.
        $this->artisan('plans:notify-daily-profit')->assertSuccessful();
        $this->assertEquals(1, UserNotification::where('user_id', $user->id)->where('type', 'daily_profit')->count());
    }

    public function test_no_notification_when_nothing_accrued_today_but_holding_is_marked_processed(): void
    {
        $user = User::factory()->create(['phone' => '9811111111']);
        $plan = $this->makePlan();

        // Purchased long ago — already accrued the full capped profit, so
        // today's increment is 0.
        UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'invested_amount' => 500,
            'daily_profit_val' => 10,
            'total_return' => null,
            'duration_label' => '3 Months',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(100),
            'matures_at' => now()->subDays(10),
        ]);

        $this->artisan('plans:notify-daily-profit')->assertSuccessful();

        $this->assertEquals(0, UserNotification::where('user_id', $user->id)->where('type', 'daily_profit')->count());
        $this->assertNotNull(UserPlan::where('user_id', $user->id)->first()->last_daily_profit_notified_at);
    }
}
