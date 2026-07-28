<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('withdrawal_min_amount', '300');
        AppSetting::set('withdrawal_daily_limit', '5000');
        AppSetting::set('withdrawal_max_per_day', '3');
    }

    public function test_rejects_withdrawal_below_minimum_amount(): void
    {
        $user = User::factory()->create(['phone' => '9100000001']);
        WalletBalance::credit('9100000001', 1000);

        $response = $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'phone' => '9100000001',
                'amount' => 100,
                'payout_upi_id' => 'test@upi',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(0, WithdrawRequest::where('phone', '9100000001')->count());
    }

    public function test_rejects_withdrawal_exceeding_daily_limit(): void
    {
        $user = User::factory()->create(['phone' => '9100000002']);
        WalletBalance::credit('9100000002', 10000);

        WithdrawRequest::create([
            'phone' => '9100000002',
            'amount' => 5000,
            'payout_upi_id' => 'test@upi',
            'status' => WithdrawRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'phone' => '9100000002',
                'amount' => 500,
                'payout_upi_id' => 'test@upi',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(1, WithdrawRequest::where('phone', '9100000002')->count());
    }

    public function test_rejects_fourth_withdrawal_request_in_one_day(): void
    {
        $user = User::factory()->create(['phone' => '9100000003']);
        WalletBalance::credit('9100000003', 10000);

        for ($i = 0; $i < 3; $i++) {
            WithdrawRequest::create([
                'phone' => '9100000003',
                'amount' => 300,
                'payout_upi_id' => 'test@upi',
                'status' => WithdrawRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'phone' => '9100000003',
                'amount' => 300,
                'payout_upi_id' => 'test@upi',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(3, WithdrawRequest::where('phone', '9100000003')->count());
    }
}
