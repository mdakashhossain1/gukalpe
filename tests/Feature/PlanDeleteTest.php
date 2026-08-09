<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PlanManagementController::destroy() - hard-delete for plans nobody has
 * ever bought (e.g. leftover test/demo plans), added 2026-08-09 alongside
 * the existing Hide/Activate toggle. Purchased plans must stay
 * un-deletable: their UserPlan rows are real holding/withdrawal history.
 */
class PlanDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'title' => 'Delete Test Plan', 'subtitle' => 'Test', 'image' => 'https://example.com/i.png',
            'icon' => 'bi-piggy-bank', 'badge' => 'Test', 'growth_rate' => 10, 'lock_duration' => '1 Day',
            'investment_amount' => 100, 'term_days' => 1, 'daily_profit' => 0.27, 'total_return' => 100.27,
            'is_active' => true, 'status' => Plan::STATUS_ACTIVE, 'auto_mature' => true,
        ], $overrides));
    }

    public function test_a_never_purchased_plan_can_be_deleted(): void
    {
        $plan = $this->plan();

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.delete', $plan))
            ->assertRedirect(route('admin.plans'));

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_a_plan_with_a_real_purchase_cannot_be_deleted(): void
    {
        $plan = $this->plan();
        $user = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        WalletBalance::credit($user->phone, 200);

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.delete', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertSame(1, UserPlan::where('plan_id', $plan->id)->count());
    }
}
