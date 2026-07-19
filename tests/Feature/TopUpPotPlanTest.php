<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanTopup;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Coverage for the "SIP-style pot" top-up flow: a user's first contribution
 * to an allow_topups plan opens one ongoing holding; every later
 * contribution adds to that SAME UserPlan row (one shared maturity date,
 * cumulative total capped at max_investment_amount) rather than creating an
 * independent purchase - the core rule the user described: "whatever we
 * have invested to this plan... when the plan ended at a certain period of
 * time we get only that much of return", not a one-time single investment.
 */
class TopUpPotPlanTest extends TestCase
{
    use RefreshDatabase;

    private function topupPlan(float $min = 500, float $max = 10000): Plan
    {
        $plan = Plan::create([
            'title' => 'SIP Premium Plan',
            'subtitle' => 'Keep adding until you hit the max',
            'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3',
            'icon' => 'bi-piggy-bank',
            'badge' => 'Trending',
            'growth_rate' => 12,
            'lock_duration' => 'Flexible',
            'investment_amount' => $min,
            'min_investment_amount' => $min,
            'max_investment_amount' => $max,
            'allow_topups' => true,
            'daily_profit' => 0,
            'total_return' => 0,
            'min_goal' => $min,
            'is_active' => true,
            'sort_order' => 60,
        ]);

        PlanDuration::create([
            'plan_id' => $plan->id,
            'label' => '6 Months',
            'duration_days' => 180,
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

    public function test_second_contribution_tops_up_the_same_holding_instead_of_creating_a_new_one(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 2000,
        ]);
        $first = UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'amount' => 2000,
        ]);

        $this->assertSame(1, UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->count());

        $first->refresh();
        $this->assertEquals(4000.0, (float) $first->invested_amount);
        $this->assertSame(2, PlanTopup::where('user_plan_id', $first->id)->count());
    }

    public function test_maturity_date_does_not_change_when_topping_up(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 2000,
        ]);
        $pot = UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();
        $originalMaturesAt = $pot->matures_at->timestamp;
        $originalPurchasedAt = $pot->purchased_at->timestamp;

        $this->travel(5)->days();
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), ['amount' => 3000]);

        $pot->refresh();
        $this->assertSame($originalMaturesAt, $pot->matures_at->timestamp);
        $this->assertSame($originalPurchasedAt, $pot->purchased_at->timestamp);
    }

    public function test_return_is_computed_on_the_final_cumulative_total_not_each_individual_contribution(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        // 12%/yr over 180 days on ₹2000, then top up to ₹6000 total.
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 2000,
        ]);
        $pot = UserPlan::where('user_id', $user->id)->firstOrFail();
        $firstReturn = (float) $pot->total_return;

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), ['amount' => 4000]);
        $pot->refresh();

        // Return on the full ₹6000 should be 3x the return that was
        // promised on the initial ₹2000 alone (same rate, same duration).
        $this->assertEqualsWithDelta($firstReturn * 3, (float) $pot->total_return, 0.5);
        $this->assertEquals(6000.0, (float) $pot->invested_amount);
    }

    public function test_cumulative_total_cannot_exceed_the_admin_configured_max(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan(min: 500, max: 5000);
        $duration = $plan->durations->first();

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 4000,
        ]);

        $overCap = $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), ['amount' => 2000]);
        $overCap->assertSessionHasErrors('plan');

        $pot = UserPlan::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(4000.0, (float) $pot->invested_amount);

        // Exactly enough to hit the cap is allowed.
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), ['amount' => 1000]);
        $pot->refresh();
        $this->assertEquals(5000.0, (float) $pot->invested_amount);
    }

    public function test_each_top_up_only_debits_the_new_amount_not_the_running_total(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 2000,
        ]);
        $this->assertEquals(98000.0, WalletBalance::balanceFor($user->phone));

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), ['amount' => 3000]);
        $this->assertEquals(95000.0, WalletBalance::balanceFor($user->phone));
    }

    public function test_maturity_credits_the_full_cumulative_amount_from_all_topups(): void
    {
        $user = $this->userWithWallet(0);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        $pot = UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_duration_id' => $duration->id,
            'invested_amount' => 6000,
            'daily_profit_val' => 720 / 180, // 12% of 6000 over 180 days
            'total_return' => 6720,
            'duration_label' => '6 Months',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(181),
            'matures_at' => now()->subDay(),
        ]);
        PlanTopup::create(['user_plan_id' => $pot->id, 'amount' => 2000]);
        PlanTopup::create(['user_plan_id' => $pot->id, 'amount' => 4000]);

        Artisan::call('plans:mature-holdings');
        $pot->refresh();

        $this->assertSame(UserPlan::STATUS_WITHDRAWN, $pot->status);
        $this->assertEqualsWithDelta(6720.0, WalletBalance::balanceFor($user->phone), 0.5);
    }

    public function test_topup_is_rejected_once_the_pot_has_already_matured(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_duration_id' => $duration->id,
            'invested_amount' => 2000,
            'daily_profit_val' => 1,
            'total_return' => 2240,
            'duration_label' => '6 Months',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(181),
            'matures_at' => now()->subDay(), // already past maturity, not yet swept by the scheduler
        ]);

        // A late-arriving top-up must open a BRAND NEW pot, not silently
        // extend the already-finished one's shared maturity date.
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 1000,
        ]);

        $this->assertSame(2, UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->count());
    }

    public function test_plan_details_shows_pot_status_and_add_more_widget_once_a_pot_is_open(): void
    {
        $user = $this->userWithWallet(100000);
        $plan = $this->topupPlan();
        $duration = $plan->durations->first();

        // Before any contribution: the initial "Choose Your Investment" slider.
        $before = $this->actingAs($user)->get(route('plan-details', $plan, absolute: false));
        $before->assertSee('Choose Your Investment');
        $before->assertDontSee('Your Investment Pot');

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false), [
            'duration_id' => $duration->id,
            'amount' => 2000,
        ]);

        // After the first contribution: the pot status + "Add More" widget,
        // not the initial slider again.
        $after = $this->actingAs($user)->get(route('plan-details', $plan, absolute: false));
        $after->assertSee('Your Investment Pot');
        $after->assertSee('pd-topup-slider', false);
        $after->assertDontSee('Choose Your Investment');
    }
}
