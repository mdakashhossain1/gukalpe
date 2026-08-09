<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * GET /cron/run - the curl-triggered fallback for `php artisan schedule:run`
 * on hosts with no native cron access (see MEMORY.md 2026-08-09). Confirms
 * the token gate actually gates, and that a correct hit really invokes
 * schedule:run.
 *
 * Doesn't assert an end-to-end matured-holding side effect here: Laravel's
 * scheduler runs each due `$schedule->command(...)` as a separate OS
 * subprocess (see Illuminate\Console\Scheduling\Event::run()), which has its
 * own DB connection and real (non-frozen) clock - decoupled from this test's
 * RefreshDatabase transaction and Carbon::setTestNow(), so it can't be
 * observed from here. plans:mature-holdings' actual maturity/credit logic is
 * already covered directly by MaturePlanHoldingsTest and
 * FixedPlanNoDurationMaturityTest.
 */
class CronEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cron.secret' => 'test-secret-value']);
    }

    public function test_request_without_token_is_forbidden(): void
    {
        $this->get('/cron/run')->assertForbidden();
    }

    public function test_request_with_wrong_token_is_forbidden(): void
    {
        $this->get('/cron/run?token=wrong')->assertForbidden();
    }

    public function test_request_is_forbidden_when_no_secret_is_configured(): void
    {
        config(['cron.secret' => null]);

        $this->get('/cron/run?token=anything')->assertForbidden();
    }

    public function test_correct_token_invokes_schedule_run(): void
    {
        Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        $this->get('/cron/run?token=test-secret-value')->assertOk()->assertSee('OK');
    }
}
