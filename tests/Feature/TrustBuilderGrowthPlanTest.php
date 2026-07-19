<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * End-to-end coverage for the Trust Builder / Growth Plan unlock system
 * (plans.md) - real HTTP requests through PlanPurchaseController, exactly
 * the guard order/session-flash contract the pure-CSS popups (Phase 2)
 * depend on, plus the plans:mature-holdings scheduler command (Phase 1).
 */
class TrustBuilderGrowthPlanTest extends TestCase
{
    use RefreshDatabase;

    private function growthPlan(): Plan
    {
        return Plan::where('plan_type', 'growth')->with('durations')->firstOrFail();
    }

    private function trustBuilderPlan(): Plan
    {
        return Plan::where('plan_type', 'trust_builder')->with('durations')->firstOrFail();
    }

    // This repo's APP_URL includes a subpath (dual XAMPP/artisan-serve
    // front controller, see CLAUDE.md) that only the real .htaccess
    // rewrites - the PHPUnit test kernel doesn't, so route()'s default
    // absolute URL 404s here. absolute: false gives the router-relative
    // path the test client actually needs.
    private function purchaseUrl(Plan $plan): string
    {
        return route('plans.purchase', $plan, absolute: false);
    }

    private function userWithWallet(float $balance): User
    {
        $user = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        WalletBalance::credit($user->phone, $balance);

        return $user;
    }

    public function test_trust_builder_purchase_is_blocked_until_growth_plan_purchased(): void
    {
        $user = $this->userWithWallet(1000);
        $trustBuilder = $this->trustBuilderPlan();

        $response = $this->actingAs($user)->post($this->purchaseUrl($trustBuilder));

        $response->assertRedirect();
        $response->assertSessionHas('open_unlock_popup', $trustBuilder->id);
        $this->assertSame(0, UserPlan::where('user_id', $user->id)->where('plan_id', $trustBuilder->id)->count());
        $this->assertEquals(1000.0, WalletBalance::balanceFor($user->phone));
    }

    public function test_growth_plan_purchase_unlocks_trust_builder_and_snapshots_selected_duration(): void
    {
        $user = $this->userWithWallet(1000);
        $growth = $this->growthPlan();
        $sixMonths = $growth->durations->firstWhere('label', '6 Months');

        $response = $this->actingAs($user)->post($this->purchaseUrl($growth), [
            'duration_id' => $sixMonths->id,
        ]);

        $response->assertRedirect(route('portfolio'));
        $response->assertSessionHas('purchase_success', fn ($data) => $data['plan_type'] === 'growth');

        $holding = UserPlan::where('user_id', $user->id)->where('plan_id', $growth->id)->first();
        $this->assertNotNull($holding);
        $this->assertSame($sixMonths->id, $holding->plan_duration_id);
        $this->assertSame('6 Months', $holding->duration_label);
        $this->assertEqualsWithDelta(now()->addDays(180)->timestamp, $holding->matures_at->timestamp, 5);
        $this->assertEquals(1000.0 - (float) $growth->investment_amount, WalletBalance::balanceFor($user->phone));

        // Trust Builder is now unlocked and purchasable.
        $trustBuilder = $this->trustBuilderPlan();
        $unlockedResponse = $this->actingAs($user)->post($this->purchaseUrl($trustBuilder));
        $unlockedResponse->assertRedirect(route('portfolio'));
        $unlockedResponse->assertSessionHas('purchase_success', fn ($data) => $data['plan_type'] === 'trust_builder');
        $this->assertSame(1, UserPlan::where('user_id', $user->id)->where('plan_id', $trustBuilder->id)->count());

        // One-time-per-user limit blocks a second Trust Builder purchase.
        $secondAttempt = $this->actingAs($user)->post($this->purchaseUrl($trustBuilder));
        $secondAttempt->assertSessionHasErrors('plan');
        $this->assertSame(1, UserPlan::where('user_id', $user->id)->where('plan_id', $trustBuilder->id)->count());
    }

    public function test_insufficient_balance_flashes_common_popup_data(): void
    {
        $user = $this->userWithWallet(0);
        $growth = $this->growthPlan();

        $response = $this->actingAs($user)->post($this->purchaseUrl($growth), [
            'duration_id' => $growth->durations->first()->id,
        ]);

        $response->assertSessionHas('insufficient_balance', fn ($data) => $data['needed'] === (float) $growth->investment_amount
            && $data['available'] === 0.0);
        $response->assertSessionHasErrors('plan');
    }

    public function test_mature_holdings_command_credits_wallet_and_marks_withdrawn(): void
    {
        $user = $this->userWithWallet(0);
        $trustBuilder = $this->trustBuilderPlan();

        $holding = UserPlan::create([
            'user_id' => $user->id,
            'plan_id' => $trustBuilder->id,
            'plan_duration_id' => $trustBuilder->durations->first()->id,
            'invested_amount' => 199,
            'daily_profit_val' => 20,
            'duration_label' => '1 Day',
            'status' => UserPlan::STATUS_ACTIVE,
            'purchased_at' => now()->subDays(2),
            'matures_at' => now()->subDay(),
        ]);

        Artisan::call('plans:mature-holdings');

        $holding->refresh();
        $this->assertSame(UserPlan::STATUS_WITHDRAWN, $holding->status);
        $this->assertNotNull($holding->withdrawn_at);
        $this->assertGreaterThan(0, WalletBalance::balanceFor($user->phone));
        $this->assertTrue(UserNotification::where('user_id', $user->id)->where('type', 'plan_matured')->exists());
    }

    /**
     * The specific rule this test locks in: investing in the 6-month Growth
     * Plan duration must not make that money (principal or return)
     * available in the wallet - and therefore not withdrawable via
     * WithdrawRequestController - until the real 6-month matures_at date
     * has passed. Running the maturity scheduler early must be a no-op.
     */
    public function test_six_month_plan_return_is_not_accessible_before_six_months_are_up(): void
    {
        $user = $this->userWithWallet(1000);
        $growth = $this->growthPlan();
        $sixMonths = $growth->durations->firstWhere('label', '6 Months');

        $this->actingAs($user)->post($this->purchaseUrl($growth), ['duration_id' => $sixMonths->id]);

        $holding = UserPlan::where('user_id', $user->id)->where('plan_id', $growth->id)->firstOrFail();
        $this->assertEqualsWithDelta(now()->addDays(180)->timestamp, $holding->matures_at->timestamp, 5);

        $balanceAfterPurchase = WalletBalance::balanceFor($user->phone);
        $this->assertEquals(1000.0 - (float) $growth->investment_amount, $balanceAfterPurchase);

        // Running the scheduler with 6 months still to go must not touch
        // this holding at all - neither credit the wallet nor mark it withdrawn.
        Artisan::call('plans:mature-holdings');
        $holding->refresh();
        $this->assertSame(UserPlan::STATUS_ACTIVE, $holding->status);
        $this->assertEquals($balanceAfterPurchase, WalletBalance::balanceFor($user->phone));

        // The invested amount + expected return are therefore not in the
        // wallet yet, so a withdrawal request for that amount is correctly
        // rejected as insufficient balance - only whatever was already in
        // the wallet (here, nothing) can be requested.
        $withdrawResponse = $this->post('/withdraw-money', [
            'phone' => $user->phone,
            'amount' => (float) $sixMonths->total_return,
            'payout_upi_id' => 'test@upi',
        ]);
        $withdrawResponse->assertSessionHasErrors('amount');

        // Fast-forward past the real maturity date. currentHolding()'s
        // accrual is driven by days elapsed since purchased_at (capped at
        // the plan's total_return), so both purchased_at and matures_at
        // need to move together to realistically simulate "180 days later"
        // rather than just relabeling matures_at while 0 real days have
        // elapsed - which would under-credit and fail this assertion.
        $holding->update([
            'purchased_at' => now()->subDays(181),
            'matures_at' => now()->subMinute(),
        ]);
        Artisan::call('plans:mature-holdings');
        $holding->refresh();

        $this->assertSame(UserPlan::STATUS_WITHDRAWN, $holding->status);
        // Credited on top of whatever was already in the wallet, not a
        // replacement of it - the promised return for THIS specific
        // duration (6 Months' own 535.92, not the plan's base-row 3-month
        // figure of 513.76 - this is the regression check for that bug).
        $this->assertEquals($balanceAfterPurchase + (float) $sixMonths->total_return, WalletBalance::balanceFor($user->phone));
    }
}
