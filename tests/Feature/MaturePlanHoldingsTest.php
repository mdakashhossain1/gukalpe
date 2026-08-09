<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaturePlanHoldingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_investment_plus_profit_to_wallet_on_maturity(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);
        WalletBalance::firstOrCreate(['phone' => '9876543210'], ['balance' => 0]);

        $plan = Plan::create([
            'title' => 'Test Plan',
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

        UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'invested_amount' => 500,
            'daily_profit_val' => 10,
            'total_return' => null,
            'duration_label' => '3 Months',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(30),
            'matures_at' => now()->subDay(),
        ]);

        $this->artisan('plans:mature-holdings');

        // 10/day * 30 days = 300 profit, capped at maxProfit (800-500=300) since
        // total_return is 800. invested (500) + profit (300) = 800 credited -
        // both principal and profit now return to the wallet at maturity.
        $this->assertEquals(800.0, WalletBalance::balanceFor('9876543210'));
    }
}
