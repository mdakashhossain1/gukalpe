<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * End-to-end regression coverage that the existing unit-style Plan tests
 * don't provide: those construct Plan/PlanDuration rows directly or purchase
 * against migration-seeded data (the Growth/Trust Builder plans from
 * 2026_07_19_010300_seed_trust_builder_and_growth_plans.php), never through
 * PlanManagementController's actual store()/syncDurations() pipeline. These
 * tests instead go through the real admin.plans.store POST route to create
 * a plan the way an admin actually would, then purchase it as a customer -
 * closing the gap where an admin-panel change (like the 2026-08-12
 * Fixed/Flexible Duration-options rework) could pass every other test yet
 * still leave freshly-created plans unpurchasable.
 */
class PlanAdminCreatedPlanIsPurchasableTest extends TestCase
{
    use RefreshDatabase;

    private function purchaseUrl(Plan $plan): string
    {
        return route('plans.purchase', $plan, absolute: false);
    }

    public function test_admin_created_fixed_plan_is_purchasable_end_to_end(): void
    {
        $fields = [
            'title' => 'Smoke Fixed Plan',
            'subtitle' => 'Sub',
            'badge' => 'General',
            'badge_icon' => '',
            'plan_type' => '',
            'status' => 'active',
            'investment_mode' => 'fixed',
            'growth_rate' => 12,
            'term_days' => 90,
            'lock_duration' => '90 Days',
            'investment_amount' => 500,
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('plan.jpg'),
        ];

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Smoke Fixed Plan')->with('durations')->firstOrFail();
        $this->assertCount(1, $plan->durations);

        $user = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        WalletBalance::credit($user->phone, 1000);

        $response = $this->actingAs($user)->post($this->purchaseUrl($plan));

        $response->assertRedirect(route('portfolio'));
        $response->assertSessionHasNoErrors();
        $this->assertEquals(500.0, WalletBalance::balanceFor($user->phone));

        $path = public_path($plan->image);
        if ($plan->image && is_file($path)) {
            @unlink($path);
        }
    }

    public function test_admin_created_flexible_plan_is_purchasable_end_to_end(): void
    {
        $fields = [
            'title' => 'Smoke Flexible Plan',
            'subtitle' => 'Sub',
            'badge' => 'General',
            'badge_icon' => '',
            'plan_type' => '',
            'status' => 'active',
            'investment_mode' => 'flexible',
            'min_investment_amount' => 100,
            'max_investment_amount' => 5000,
            'lock_duration' => 'Flexible',
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('plan.jpg'),
            'durations' => [
                ['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12],
                ['label' => '6 Months', 'duration_days' => 180, 'growth_rate' => 15],
            ],
            'duration_default' => 0,
        ];

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Smoke Flexible Plan')->with('durations')->firstOrFail();
        $this->assertCount(2, $plan->durations);
        $sixMonth = $plan->durations->firstWhere('label', '6 Months');

        $user = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        WalletBalance::credit($user->phone, 2000);

        $response = $this->actingAs($user)->post($this->purchaseUrl($plan), [
            'amount' => 1000,
            'duration_id' => $sixMonth->id,
        ]);

        $response->assertRedirect(route('portfolio'));
        $response->assertSessionHasNoErrors();
        $this->assertEquals(1000.0, WalletBalance::balanceFor($user->phone));

        $path = public_path($plan->image);
        if ($plan->image && is_file($path)) {
            @unlink($path);
        }
    }
}
