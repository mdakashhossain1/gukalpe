<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlanAutoComputeAndTrustBuilderTest extends TestCase
{
    use RefreshDatabase;

    // PlanManagementController::storeUploadedImage() writes straight to
    // public_path() via UploadedFile::move() (not the Storage facade - see
    // its own doc comment on why), so Storage::fake() can't intercept these
    // uploads and RefreshDatabase only resets the DB, not the filesystem.
    // Every fake image this test class's POSTs create on disk must be
    // cleaned up manually or it accumulates in public/assets/plans forever.
    protected function tearDown(): void
    {
        foreach (Plan::where('title', 'Auto Compute Plan')->get() as $plan) {
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
            'title' => 'Auto Compute Plan',
            'subtitle' => 'Sub',
            'badge' => 'General',
            'badge_icon' => '',
            'plan_type' => '',
            'status' => 'active',
            'investment_mode' => 'fixed',
            'growth_rate' => 10,
            'term_days' => 365,
            'lock_duration' => '1 Year',
            'investment_amount' => 1000,
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('plan.jpg'),
        ], $overrides);
    }

    public function test_create_form_renders_with_live_preview_panel(): void
    {
        $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.plans.create'))
            ->assertOk()
            ->assertSee('Live preview')
            ->assertSee('id="lp-title"', false);
    }

    public function test_edit_form_renders_with_live_preview_panel_for_an_existing_plan(): void
    {
        $plan = Plan::where('plan_type', 'trust_builder')->firstOrFail();

        $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.plans.edit', $plan))
            ->assertOk()
            ->assertSee('Live preview');
    }

    public function test_daily_profit_and_total_return_are_ignored_from_request_and_computed_server_side(): void
    {
        $fields = $this->baseFields([
            // Tampered values a malicious/careless admin might submit directly.
            'daily_profit' => 99999,
            'total_return' => 99999,
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Auto Compute Plan')->firstOrFail();

        // total = 1000 * (1 + (10/100) * (365/365)) = 1100; daily = 100/365
        $this->assertEquals(1100.00, (float) $plan->total_return);
        $this->assertEqualsWithDelta(0.27, (float) $plan->daily_profit, 0.01);
    }

    public function test_term_days_is_required_when_plan_has_no_duration_rows(): void
    {
        $fields = $this->baseFields(['term_days' => null]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertSessionHasErrors('term_days');

        $this->assertDatabaseMissing('plans', ['title' => 'Auto Compute Plan']);
    }

    public function test_trust_builder_plan_type_forces_single_one_day_duration_and_auto_mature(): void
    {
        $fields = $this->baseFields([
            'plan_type' => 'trust_builder',
            'auto_mature' => '0', // deliberately submitted false - server must override to true
            // Attempt to smuggle a real multi-duration setup in anyway.
            'durations' => [
                ['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12],
                ['label' => '6 Months', 'duration_days' => 180, 'growth_rate' => 15],
            ],
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Auto Compute Plan')->with('durations')->firstOrFail();

        $this->assertTrue($plan->auto_mature);
        $this->assertCount(1, $plan->durations);
        $this->assertEquals(1, $plan->durations->first()->duration_days);
        $this->assertEquals('1 Day', $plan->durations->first()->label);
    }

    public function test_growth_plan_type_keeps_normal_multi_duration_behavior(): void
    {
        $fields = $this->baseFields([
            'plan_type' => 'growth',
            'durations' => [
                ['label' => '3 Months', 'duration_days' => 90, 'growth_rate' => 12],
                ['label' => '6 Months', 'duration_days' => 180, 'growth_rate' => 15],
            ],
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.plans.store'), $fields)
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::where('title', 'Auto Compute Plan')->with('durations')->firstOrFail();

        $this->assertCount(2, $plan->durations);
    }
}
