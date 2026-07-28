<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanOutOfStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_purchase_when_plan_is_out_of_stock(): void
    {
        $user = User::factory()->create(['phone' => '9000000001']);
        WalletBalance::credit('9000000001', 1000);

        $plan = Plan::create([
            'title' => 'Limited Stock Plan',
            'subtitle' => 'Limited',
            'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank',
            'investment_amount' => 100,
            'daily_profit' => 1,
            'total_return' => 130,
            'growth_rate' => 10,
            'lock_duration' => '30 Days',
            'badge' => 'General',
            'is_active' => true,
            'max_purchases' => 5,
            'total_purchases_count' => 5,
        ]);

        $response = $this->actingAs($user)
            ->post(route('plans.purchase', $plan));

        $response->assertRedirect();
        $response->assertSessionHasErrors('plan');

        $this->assertEquals(1000.0, WalletBalance::balanceFor('9000000001'));
    }
}
