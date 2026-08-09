<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Regression test for the bug reported 2026-08-09: a Fixed plan created
 * using only the top-level Investment/Growth rate/Term (days) fields (no
 * Duration rows added) never matured - PlanPurchaseController left
 * matures_at null when $duration was null, so UserPlan::scopeMatured()
 * (whereNotNull('matures_at')) never picked the holding up and
 * plans:mature-holdings silently never credited the promised profit.
 */
class FixedPlanNoDurationMaturityTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_plan_with_no_duration_rows_still_gets_a_maturity_date_and_matures(): void
    {
        $plan = Plan::create([
            'title' => 'One Day Fixed Plan',
            'subtitle' => 'Test',
            'image' => 'https://example.com/img.png',
            'icon' => 'bi-piggy-bank',
            'badge' => 'Test',
            'growth_rate' => 10,
            'lock_duration' => '1 Day',
            'investment_amount' => 100,
            'term_days' => 1,
            'daily_profit' => 0.27,
            'total_return' => 100.27,
            'is_active' => true,
            'status' => Plan::STATUS_ACTIVE,
            'auto_mature' => true,
        ]);

        $this->assertTrue($plan->durations()->count() === 0, 'Precondition: plan has no duration rows.');

        $user = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        WalletBalance::credit($user->phone, 200);

        $response = $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));
        $response->assertRedirect(route('portfolio'));

        $holding = UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();

        // The actual bug: this used to be null.
        $this->assertNotNull($holding->matures_at, 'matures_at must be set from plan->term_days when there is no duration row.');
        $this->assertEqualsWithDelta(now()->addDay()->timestamp, $holding->matures_at->timestamp, 5);

        // Fast-forward past maturity and run the real nightly scheduler command.
        // Both purchased_at and matures_at move back together - currentHolding()
        // accrues off real elapsed wall-clock days since purchased_at, so only
        // rewinding matures_at (0 real days elapsed) would under-report profit.
        $holding->update(['purchased_at' => now()->subDay(), 'matures_at' => now()->subMinute()]);

        Artisan::call('plans:mature-holdings');

        $holding->refresh();
        $this->assertSame(UserPlan::STATUS_WITHDRAWN, $holding->status);
        $this->assertEqualsWithDelta(200 - 100 + 0.27, WalletBalance::balanceFor($user->phone), 0.01);
    }
}
