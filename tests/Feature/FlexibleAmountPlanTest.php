<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Coverage for the "invest any amount" flow (plans.md's Premium Plan
 * example - Min/Max Investment, live slider on Plan Details): a plan with
 * min_investment_amount < max_investment_amount lets the user pick their
 * own amount, and the return is computed proportionally from the selected
 * duration's growth_rate x that amount, not a fixed plan-level figure.
 */
class FlexibleAmountPlanTest extends TestCase
{
    use RefreshDatabase;

    private function flexiblePlan(): Plan
    {
        $plan = Plan::create([
            'title' => 'Premium Plan',
            'subtitle' => 'Invest any amount you like',
            'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3',
            'icon' => 'bi-rocket-takeoff',
            'badge' => 'Trending',
            'growth_rate' => 12,
            'lock_duration' => 'Flexible',
            'investment_amount' => 500,
            'min_investment_amount' => 500,
            'max_investment_amount' => 50000,
            'daily_profit' => 0,
            'total_return' => 0,
            'min_goal' => 500,
            'is_active' => true,
            'sort_order' => 50,
        ]);

        PlanDuration::create([
            'plan_id' => $plan->id,
            'label' => '1 Year',
            'duration_days' => 365,
            'growth_rate' => 12,
            'daily_profit' => 0,
            'total_return' => 0,
            'is_default' => true,
            'sort_order' => 0,
        ]);

        return $plan->fresh(['durations']);
    }

    private function userWithWallet(float $balance): User
    {
        $user = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        WalletBalance::credit($user->phone, $balance);

        return $user;
    }

    public function test_plan_is_only_flexible_when_a_real_max_greater_than_min_is_set(): void
    {
        $flexible = $this->flexiblePlan();
        $this->assertTrue($flexible->isFlexibleAmount());

        $fixed = Plan::where('plan_type', 'trust_builder')->firstOrFail();
        $this->assertFalse($fixed->isFlexibleAmount());
    }

    public function test_purchase_computes_return_proportionally_from_the_chosen_amount(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->flexiblePlan();
        $duration = $plan->durations->first();

        // ₹10,000 at 12%/yr over 365 days -> exactly a 12% return.
        $response = $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 10000,
        ]);

        $response->assertRedirect(route('portfolio'));

        $holding = UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();
        $this->assertEquals(10000.0, (float) $holding->invested_amount);
        $this->assertEqualsWithDelta(11200.0, (float) $holding->total_return, 0.01);
        $this->assertEqualsWithDelta(1200.0 / 365, (float) $holding->daily_profit_val, 0.01);
        $this->assertEquals(100000.0 - 10000.0, WalletBalance::balanceFor($user->phone));
    }

    public function test_different_amounts_on_the_same_plan_get_proportionally_different_returns(): void
    {
        $userSmall = $this->userWithWallet(100000);
        $userLarge = $this->userWithWallet(100000);
        $plan = $this->flexiblePlan();
        $duration = $plan->durations->first();

        $this->actingAs($userSmall)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 1000,
        ]);
        $this->actingAs($userLarge)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 20000,
        ]);

        $small = UserPlan::where('user_id', $userSmall->id)->firstOrFail();
        $large = UserPlan::where('user_id', $userLarge->id)->firstOrFail();

        // Same 12% rate, 20x the amount -> 20x the promised return.
        $this->assertEqualsWithDelta((float) $small->total_return * 20, (float) $large->total_return, 0.1);
    }

    public function test_amount_outside_the_admin_configured_range_is_rejected(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->flexiblePlan();
        $duration = $plan->durations->first();

        $tooLow = $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 100, // below min_investment_amount (500)
        ]);
        $tooLow->assertSessionHasErrors('plan');

        $tooHigh = $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 100000, // above max_investment_amount (50000)
        ]);
        $tooHigh->assertSessionHasErrors('plan');

        $this->assertSame(0, UserPlan::where('user_id', $user->id)->count());
        $this->assertEquals(100000.0, WalletBalance::balanceFor($user->phone));
    }

    public function test_maturity_credits_the_exact_proportional_return_that_was_promised(): void
    {
        $user = $this->userWithWallet(0);
        $plan = $this->flexiblePlan();
        $duration = $plan->durations->first();

        $holding = UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_duration_id' => $duration->id,
            'invested_amount' => 5000,
            'daily_profit_val' => 600 / 365, // 12% of 5000 over a year
            'total_return' => 5600, // the snapshot PlanPurchaseController would have stored
            'duration_label' => '1 Year',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(366),
            'matures_at' => now()->subDay(),
        ]);

        Artisan::call('plans:mature-holdings');
        $holding->refresh();

        $this->assertSame(UserPlan::STATUS_WITHDRAWN, $holding->status);
        $this->assertEqualsWithDelta(5600.0, WalletBalance::balanceFor($user->phone), 0.5);
    }
}
