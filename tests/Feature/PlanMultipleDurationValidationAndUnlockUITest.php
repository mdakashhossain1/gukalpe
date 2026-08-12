<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlanMultipleDurationValidationAndUnlockUITest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Plan::where('title', 'like', 'MultiDur Test%')->get() as $plan) {
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
            'title' => 'MultiDur Test Plan',
            'subtitle' => 'Sub',
            'badge' => 'General',
            'badge_icon' => '',
            'plan_type' => 'growth',
            'status' => 'active',
            'lock_duration' => 'Multiple',
            'investment_mode' => 'fixed',
            'investment_amount' => 499,
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('plan.jpg'),
        ], $overrides);
    }

    public function test_multiple_duration_mode_does_not_require_top_level_growth_rate_or_term_days(): void
    {
        // Multiple Duration is a Flexible-mode feature - Fixed plans always
        // collapse to a single top-level rate/term (see
        // PlanInvestmentModeEnforcementTest for that guarantee).
        $fields = $this->baseFields([
            'investment_mode' => 'flexible',
            'min_investment_amount' => 499,
            'max_investment_amount' => 5000,
            'durations' => [
                ['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12],
                ['label' => '6 Months', 'duration_days' => 180, 'growth_rate' => 15],
                ['label' => '1 Year', 'duration_days' => 365, 'growth_rate' => 18],
                ['label' => '2 Years', 'duration_days' => 730, 'growth_rate' => 20],
            ],
        ]);
        // Top-level growth_rate and term_days omitted deliberately for Multiple Duration plan

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'MultiDur Test Plan')->firstOrFail();
        $this->assertCount(4, $plan->durations);
        $this->assertSame(12, (int) $plan->durations->firstWhere('label', '3 Months')->growth_rate);
    }

    public function test_single_duration_mode_requires_top_level_growth_rate_and_term_days(): void
    {
        $fields = $this->baseFields();
        unset($fields['growth_rate'], $fields['term_days']);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertSessionHasErrors(['growth_rate', 'term_days']);
    }
}
