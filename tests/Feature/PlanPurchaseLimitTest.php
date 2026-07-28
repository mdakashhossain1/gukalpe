<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPurchaseLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_out_of_stock_when_total_purchases_count_equals_max_purchases(): void
    {
        $plan = Plan::create([
            'title' => 'Limited Plan',
            'subtitle' => 'Limited',
            'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank',
            'investment_amount' => 100,
            'daily_profit' => 1,
            'total_return' => 120,
            'growth_rate' => 10,
            'lock_duration' => '30 Days',
            'badge' => 'General',
            'max_purchases' => 2,
            'total_purchases_count' => 2,
        ]);

        $this->assertTrue($plan->isOutOfStock());
    }

    public function test_is_not_out_of_stock_when_unlimited(): void
    {
        $plan = Plan::create([
            'title' => 'Unlimited Plan',
            'subtitle' => 'Unlimited',
            'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank',
            'investment_amount' => 100,
            'daily_profit' => 1,
            'total_return' => 120,
            'growth_rate' => 10,
            'lock_duration' => '30 Days',
            'badge' => 'General',
            'max_purchases' => null,
            'total_purchases_count' => 5,
        ]);

        $this->assertFalse($plan->isOutOfStock());
    }
}
