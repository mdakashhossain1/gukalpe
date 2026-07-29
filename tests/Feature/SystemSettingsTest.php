<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(): Plan
    {
        return Plan::create([
            'title' => 'Switch Plan', 'subtitle' => 'Test', 'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank', 'investment_amount' => 500, 'daily_profit' => 10,
            'total_return' => 800, 'growth_rate' => 10, 'lock_duration' => '3 Months',
            'badge' => 'General', 'auto_mature' => true, 'is_active' => true,
        ]);
    }

    public function test_allow_investment_off_blocks_purchase(): void
    {
        $user = User::factory()->create(['phone' => '9990001111']);
        WalletBalance::firstOrCreate(['phone' => '9990001111'], ['balance' => 100000]);
        $plan = $this->makePlan();
        AppSetting::set('allow_investment', 'false');

        $this->actingAs($user)
            ->post(route('plans.purchase', $plan), ['duration_id' => ''])
            ->assertSessionHasErrors(['plan']);

        $this->assertEquals(0, UserPlan::where('user_id', $user->id)->count());
    }

    public function test_allow_withdrawals_off_blocks_withdrawal(): void
    {
        $user = User::factory()->create(['phone' => '9990001111']);
        WalletBalance::firstOrCreate(['phone' => '9990001111'], ['balance' => 100000]);
        AppSetting::set('allow_withdrawals', 'false');

        $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'phone' => '9990001111', 'amount' => 500, 'payout_upi_id' => 'test@okhdfc',
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertEquals(0, WithdrawRequest::where('phone', '9990001111')->count());
    }

    public function test_allow_registration_off_blocks_new_phone_but_not_existing_user(): void
    {
        AppSetting::set('allow_registration', 'false');

        // Brand-new phone → blocked.
        $this->post(route('login.submit'), ['phone' => '9990007777', 'terms' => '1'])
            ->assertSessionHasErrors(['phone']);
        $this->assertEquals(0, User::where('phone', '9990007777')->count());

        // Existing user with an MPIN → still allowed to log in (no error).
        User::factory()->create(['phone' => '9990001111', 'mpin' => '1234']);
        $this->post(route('login.submit'), ['phone' => '9990001111', 'terms' => '1'])
            ->assertRedirect(route('login.mpin'))
            ->assertSessionHasNoErrors();
    }

    public function test_maintenance_mode_blocks_public_but_not_admin_panel(): void
    {
        AppSetting::set('maintenance_mode', 'true');

        $this->get('/')->assertStatus(503)->assertSee('maintenance', false);

        // Admin panel must stay reachable so it can be switched back off.
        $this->get('/'.config('admin.panel_slug'))->assertStatus(200);
    }
}
