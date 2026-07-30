<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'title' => 'Analytics Plan', 'subtitle' => 't', 'image' => 'a.jpg', 'icon' => 'bi-piggy-bank',
            'investment_amount' => 500, 'daily_profit' => 5, 'total_return' => 800, 'growth_rate' => 10,
            'lock_duration' => '1 Day', 'badge' => 'General', 'is_active' => true,
        ], $overrides));
    }

    public function test_opening_plan_details_increments_the_view_counter(): void
    {
        $plan = $this->makePlan();
        $this->assertEquals(0, $plan->views);

        $this->get(route('plan-details', $plan))->assertOk();
        $this->get(route('plan-details', $plan))->assertOk();

        $this->assertEquals(2, $plan->fresh()->views);
    }

    public function test_analytics_page_shows_per_plan_aggregates(): void
    {
        $plan = $this->makePlan(['views' => 100]);
        $user = User::factory()->create(['phone' => '9990000001']);

        // 2 running + 1 completed holding, invested 500 + 300 + 200 = 1000
        foreach ([[500, 'active'], [300, 'active'], [200, 'withdrawn']] as [$amt, $status]) {
            UserPlan::create([
                'user_id' => $user->id, 'plan_id' => $plan->id, 'invested_amount' => $amt,
                'daily_profit_val' => 5, 'duration_label' => '1 Day', 'status' => $status,
                'purchased_at' => now()->subDay(), 'matures_at' => now(),
            ]);
        }

        $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.plan-analytics'))
            ->assertOk()
            ->assertSee('Analytics Plan')
            ->assertSee('3.0%')            // conversion: 3 purchases / 100 views
            ->assertSee('1,000.00');       // total invested
    }
}
