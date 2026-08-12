<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Regression coverage for the 2026-08-09 "Premium Plan" incident: a plan
 * saved with Min investment set but Max left blank silently behaved as a
 * Fixed plan (everyone charged the same amount) while its own subtitle
 * promised a slider - because investment_mode was submitted by the form but
 * never read by PlanManagementController. These tests make the Fixed/
 * Flexible switcher server-authoritative: Flexible requires a real Min+Max
 * range; Fixed always wipes any stray Min/Max/step/top-ups values regardless
 * of what was submitted.
 *
 * 2026-08-12: Duration options (a per-term choice - 3mo/6mo/1yr, each its
 * own rate) is exclusively a Flexible-mode feature now - Flexible requires
 * at least one admin-submitted Duration row (unchanged rule, restored after
 * a brief detour). Fixed plans always collapse to exactly one Duration row
 * derived from their own top-level growth_rate/term_days - any Duration
 * rows submitted for a Fixed plan (e.g. a direct/tampered request) are
 * silently discarded, same guarantee Trust Builder already had.
 */
class PlanInvestmentModeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Plan::where('title', 'like', 'Mode Test%')->get() as $plan) {
            $path = public_path($plan->image);
            if ($plan->image && is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    private function baseFields(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Mode Test Plan',
            'subtitle' => 'Sub',
            'badge' => 'General',
            'badge_icon' => '',
            'plan_type' => '',
            'status' => 'active',
            'growth_rate' => 10,
            'term_days' => 365,
            'lock_duration' => '1 Year',
            'investment_amount' => 199,
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('plan.jpg'),
        ], $overrides);
    }

    public function test_flexible_mode_with_only_min_set_is_rejected(): void
    {
        $fields = $this->baseFields([
            'investment_mode' => 'flexible',
            'min_investment_amount' => 500,
            'durations' => [['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12]],
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertSessionHasErrors('max_investment_amount');

        $this->assertDatabaseMissing('plans', ['title' => 'Mode Test Plan']);
    }

    public function test_flexible_mode_without_any_duration_row_is_rejected(): void
    {
        $fields = $this->baseFields([
            'investment_mode' => 'flexible',
            'min_investment_amount' => 100,
            'max_investment_amount' => 1000,
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertSessionHasErrors('investment_mode');

        $this->assertDatabaseMissing('plans', ['title' => 'Mode Test Plan']);
    }

    public function test_flexible_mode_with_a_real_range_and_duration_saves_correctly(): void
    {
        $fields = $this->baseFields([
            'investment_mode' => 'flexible',
            'min_investment_amount' => 100,
            'max_investment_amount' => 1000,
            'durations' => [
                ['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12],
                ['label' => '6 Months', 'duration_days' => 180, 'growth_rate' => 15],
            ],
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Mode Test Plan')->firstOrFail();

        $this->assertTrue($plan->isFlexibleAmount());
        // investment_amount is pinned to Min, not left at whatever the admin
        // typed in the (now Fixed-only) Investment field.
        $this->assertEquals(100.0, (float) $plan->investment_amount);
        // Both submitted rows are kept - Flexible is where a real per-term
        // choice lives.
        $this->assertCount(2, $plan->durations);
        $this->assertEquals(12, $plan->durations->firstWhere('label', '3 Months')->growth_rate);
        $this->assertEquals(15, $plan->durations->firstWhere('label', '6 Months')->growth_rate);
    }

    public function test_fixed_mode_wipes_stray_min_max_values_even_if_submitted(): void
    {
        // Simulates an admin who toggled to Flexible, typed a Min/Max, then
        // toggled back to Fixed before saving - the hidden fields aren't
        // disabled pre-JS-fix, so a direct/tampered POST can still carry
        // stray values through.
        $fields = $this->baseFields([
            'investment_mode' => 'fixed',
            'min_investment_amount' => 100,
            'max_investment_amount' => 1000,
            'allow_topups' => '1',
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Mode Test Plan')->firstOrFail();

        $this->assertFalse($plan->isFlexibleAmount());
        $this->assertNull($plan->min_investment_amount);
        $this->assertNull($plan->max_investment_amount);
        $this->assertFalse($plan->allow_topups);
    }

    public function test_fixed_mode_ignores_submitted_duration_rows_and_derives_one_from_top_level_fields(): void
    {
        // The Duration options section is hidden for Fixed mode - a
        // direct/tampered request submitting rows here must not create real
        // multi-duration options, same guarantee as Trust Builder.
        $fields = $this->baseFields([
            'investment_mode' => 'fixed',
            'growth_rate' => 11,
            'term_days' => 90,
            'lock_duration' => '90 Days',
            'durations' => [
                ['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12],
                ['label' => '6 Months', 'duration_days' => 180, 'growth_rate' => 15],
            ],
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Mode Test Plan')->firstOrFail();

        $this->assertFalse($plan->isFlexibleAmount());
        $this->assertCount(1, $plan->durations);
        $this->assertEquals('90 Days', $plan->durations->first()->label);
        $this->assertEquals(11, $plan->durations->first()->growth_rate);
        $this->assertEquals(90, $plan->durations->first()->duration_days);
    }

    public function test_investment_mode_is_required(): void
    {
        $fields = $this->baseFields();
        unset($fields['investment_mode']);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertSessionHasErrors('investment_mode');
    }
}
