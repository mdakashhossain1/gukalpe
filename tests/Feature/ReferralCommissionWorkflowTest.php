<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\Plan;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the referral-commission overhaul: commissions are created
 * PENDING (no instant wallet credit) on either qualifying source, and only
 * move money once an admin Approves/Reverses them - see MEMORY.md's dated
 * entry for the full design and app/Modules/Admin/Controllers/
 * ReferralCommissionController.php for the actions under test.
 */
class ReferralCommissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixedPlan(float $amount = 2000): Plan
    {
        return Plan::create([
            'title' => 'Referral Test Plan',
            'subtitle' => 'Fixed amount plan for referral tests',
            'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3',
            'icon' => 'bi-piggy-bank',
            'badge' => 'Trending',
            'growth_rate' => 12,
            'lock_duration' => 'Flexible',
            'investment_amount' => $amount,
            'daily_profit' => 0,
            'total_return' => 0,
            'min_goal' => $amount,
            'is_active' => true,
            'sort_order' => 60,
        ]);
    }

    private function referredUserWithWallet(User $referrer, float $balance): User
    {
        $user = User::factory()->create([
            'phone' => '9'.fake()->unique()->numerify('#########'),
            'referred_by' => $referrer->id,
        ]);
        WalletBalance::credit($user->phone, $balance);

        return $user;
    }

    private function asAdmin()
    {
        return $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin']);
    }

    public function test_plan_purchase_creates_a_pending_commission_with_no_wallet_credit(): void
    {
        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));

        $commission = ReferralCommission::where('referrer_id', $referrer->id)->where('referred_user_id', $user->id)->first();
        $this->assertNotNull($commission);
        $this->assertSame(ReferralCommission::STATUS_PENDING, $commission->status);
        $this->assertSame(ReferralCommission::SOURCE_PLAN_PURCHASE, $commission->source);
        $this->assertEquals(100.0, (float) $commission->amount); // 5% default rate of 2000

        $this->assertEquals(0.0, WalletBalance::balanceFor($referrer->phone));
    }

    public function test_admin_approve_credits_the_wallet_and_writes_a_ledger_row(): void
    {
        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));
        $commission = ReferralCommission::where('referrer_id', $referrer->id)->firstOrFail();

        $this->asAdmin()->post(route('admin.referral-commissions.approve', $commission))->assertRedirect();

        $commission->refresh();
        $this->assertSame(ReferralCommission::STATUS_PAID, $commission->status);
        $this->assertNotNull($commission->reviewed_at);
        $this->assertEquals(100.0, WalletBalance::balanceFor($referrer->phone));

        $ledger = WalletTransaction::where('phone', $referrer->phone)->where('type', 'referral_commission')->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(100.0, (float) $ledger->amount);
        $this->assertSame('credit', $ledger->direction);
    }

    public function test_reject_requires_a_reason_and_moves_no_money(): void
    {
        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));
        $commission = ReferralCommission::where('referrer_id', $referrer->id)->firstOrFail();

        $this->asAdmin()->post(route('admin.referral-commissions.reject', $commission), [])
            ->assertSessionHasErrors('reason');
        $this->assertSame(ReferralCommission::STATUS_PENDING, $commission->fresh()->status);

        $this->asAdmin()->post(route('admin.referral-commissions.reject', $commission), [
            'reason' => 'Fraudulent signup pattern',
        ])->assertRedirect();

        $commission->refresh();
        $this->assertSame(ReferralCommission::STATUS_REJECTED, $commission->status);
        $this->assertSame('Fraudulent signup pattern', $commission->reason);
        $this->assertEquals(0.0, WalletBalance::balanceFor($referrer->phone));
    }

    public function test_adjust_changes_the_amount_while_still_pending(): void
    {
        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));
        $commission = ReferralCommission::where('referrer_id', $referrer->id)->firstOrFail();

        $this->asAdmin()->post(route('admin.referral-commissions.adjust', $commission), [
            'amount' => 40,
            'reason' => 'Miscalculated - correcting to the right tier',
        ])->assertRedirect();

        $commission->refresh();
        $this->assertSame(ReferralCommission::STATUS_PENDING, $commission->status);
        $this->assertEquals(40.0, (float) $commission->amount);
        $this->assertEquals(0.0, WalletBalance::balanceFor($referrer->phone));
    }

    public function test_reverse_debits_a_paid_commission_and_requires_a_reason(): void
    {
        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));
        $commission = ReferralCommission::where('referrer_id', $referrer->id)->firstOrFail();
        $this->asAdmin()->post(route('admin.referral-commissions.approve', $commission));
        $this->assertEquals(100.0, WalletBalance::balanceFor($referrer->phone));

        $this->asAdmin()->post(route('admin.referral-commissions.reverse', $commission), [])
            ->assertSessionHasErrors('reason');

        $this->asAdmin()->post(route('admin.referral-commissions.reverse', $commission), [
            'reason' => 'Referred user\'s deposit was later reversed for fraud',
        ])->assertRedirect();

        $commission->refresh();
        $this->assertSame(ReferralCommission::STATUS_REVERSED, $commission->status);
        $this->assertEquals(0.0, WalletBalance::balanceFor($referrer->phone));

        $ledger = WalletTransaction::where('phone', $referrer->phone)->where('type', 'referral_reversal')->first();
        $this->assertNotNull($ledger);
        $this->assertSame('debit', $ledger->direction);
    }

    public function test_deposit_source_commission_is_independent_of_plan_purchase_commission(): void
    {
        AppSetting::set('referral_source_deposit_enabled', 'true');

        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));

        $deposit = DepositRequest::create([
            'phone' => $user->phone,
            'amount' => 1000,
            'method' => 'upi',
            'method_label' => 'UPI',
            'utr' => 'TESTUTR123',
            'status' => DepositRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->asAdmin()->post(route('admin.deposits.approve', $deposit))->assertRedirect();

        $depositCommission = ReferralCommission::where('referrer_id', $referrer->id)
            ->where('source', ReferralCommission::SOURCE_DEPOSIT)->first();
        $this->assertNotNull($depositCommission);
        $this->assertSame(ReferralCommission::STATUS_PENDING, $depositCommission->status);
        $this->assertEquals($deposit->id, $depositCommission->deposit_request_id);
        $this->assertEquals(50.0, (float) $depositCommission->amount); // 5% of 1000

        // Both rows exist independently - one per source.
        $this->assertSame(2, ReferralCommission::where('referrer_id', $referrer->id)->count());
    }

    public function test_purchase_below_min_qualifying_amount_earns_no_commission(): void
    {
        AppSetting::set('referral_min_qualifying_amount', '500');

        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(100); // below the 500 minimum

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));

        $this->assertSame(0, ReferralCommission::where('referrer_id', $referrer->id)->count());
    }

    public function test_rewards_page_shows_the_pending_and_paid_split(): void
    {
        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);
        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));
        $pending = ReferralCommission::where('referrer_id', $referrer->id)->firstOrFail();
        $this->asAdmin()->post(route('admin.referral-commissions.approve', $pending));

        $user2 = $this->referredUserWithWallet($referrer, 10000);
        $this->actingAs($user2)->post(route('plans.purchase', $plan, absolute: false));

        $response = $this->actingAs($referrer)->get(route('rewards'));

        $response->assertOk();
        $response->assertSee('₹200.00'); // total = 100 paid + 100 pending
        $response->assertSee('pending review');
    }

    public function test_disabling_plan_purchase_source_blocks_that_commission(): void
    {
        AppSetting::set('referral_source_plan_purchase_enabled', 'false');

        $referrer = User::factory()->create(['phone' => '9'.fake()->unique()->numerify('#########')]);
        $user = $this->referredUserWithWallet($referrer, 10000);
        $plan = $this->fixedPlan(2000);

        $this->actingAs($user)->post(route('plans.purchase', $plan, absolute: false));

        $this->assertSame(0, ReferralCommission::where('referrer_id', $referrer->id)->count());
    }
}
