<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('withdrawal_min_amount', '1');
        AppSetting::set('withdrawal_daily_limit', '50000');
        AppSetting::set('withdrawal_max_per_day', '10');
        AppSetting::set('withdrawal_max_per_transaction', '50000');
    }

    private function userWithWallet(string $phone, float $balance): User
    {
        $user = User::factory()->create(['phone' => $phone]);
        WalletBalance::credit($phone, $balance);

        return $user;
    }

    public function test_create_form_shows_only_admin_enabled_methods(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'false');
        AppSetting::set('withdrawal_method_upi_enabled', 'true');
        AppSetting::set('withdrawal_method_usdt_enabled', 'false');

        $user = $this->userWithWallet('9200000001', 1000);

        $response = $this->actingAs($user)->get(route('withdrawals.create'));

        $response->assertOk();
        $response->assertSee('UPI');
        $response->assertDontSee('USDT (TRC20)');
    }

    public function test_upi_withdrawal_succeeds_with_valid_upi_id(): void
    {
        AppSetting::set('withdrawal_method_upi_enabled', 'true');
        $user = $this->userWithWallet('9200000002', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000002',
            'amount' => 500,
            'method' => 'upi',
            'payout_upi_id' => 'test@okhdfcbank',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('withdraw_requests', [
            'phone' => '9200000002', 'method' => 'upi', 'payout_upi_id' => 'test@okhdfcbank',
        ]);
    }

    public function test_bank_withdrawal_requires_matching_account_number_confirmation(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'true');
        $user = $this->userWithWallet('9200000003', 1000);

        $response = $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000003',
            'amount' => 500,
            'method' => 'bank',
            'bank_account_holder' => 'Test User',
            'bank_account_number' => '123456789012',
            'bank_account_number_confirmation' => '999999999999',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
        ]);

        $response->assertSessionHasErrors('bank_account_number');
        $this->assertEquals(0, WithdrawRequest::where('phone', '9200000003')->count());
    }

    public function test_bank_withdrawal_succeeds_with_valid_fields(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'true');
        $user = $this->userWithWallet('9200000004', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000004',
            'amount' => 500,
            'method' => 'bank',
            'bank_account_holder' => 'Test User',
            'bank_account_number' => '123456789012',
            'bank_account_number_confirmation' => '123456789012',
            'bank_ifsc' => 'hdfc0001234',
            'bank_name' => 'HDFC Bank',
        ])->assertSessionHasNoErrors();

        $withdraw = WithdrawRequest::where('phone', '9200000004')->firstOrFail();
        $this->assertEquals('bank', $withdraw->method);
        $this->assertEquals('HDFC0001234', $withdraw->bank_ifsc); // normalized to uppercase
    }

    public function test_bank_withdrawal_rejects_invalid_ifsc_format(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'true');
        $user = $this->userWithWallet('9200000005', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000005',
            'amount' => 500,
            'method' => 'bank',
            'bank_account_holder' => 'Test User',
            'bank_account_number' => '123456789012',
            'bank_account_number_confirmation' => '123456789012',
            'bank_ifsc' => 'NOTVALID',
            'bank_name' => 'HDFC Bank',
        ])->assertSessionHasErrors('bank_ifsc');
    }

    public function test_usdt_withdrawal_succeeds_with_valid_trc20_address(): void
    {
        AppSetting::set('withdrawal_method_usdt_enabled', 'true');
        $user = $this->userWithWallet('9200000006', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000006',
            'amount' => 500,
            'method' => 'usdt',
            'usdt_address' => 'TN3W4H6rK2ce4vX9YnyKymX9YnyKymX9Yn', // valid-shape TRC20: T + 33 base58 chars
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('withdraw_requests', ['phone' => '9200000006', 'method' => 'usdt']);
    }

    public function test_usdt_withdrawal_rejects_malformed_address(): void
    {
        AppSetting::set('withdrawal_method_usdt_enabled', 'true');
        $user = $this->userWithWallet('9200000007', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000007',
            'amount' => 500,
            'method' => 'usdt',
            'usdt_address' => 'not-a-real-address',
        ])->assertSessionHasErrors('usdt_address');
    }

    public function test_disabled_method_is_rejected_even_if_posted_directly(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'false');
        $user = $this->userWithWallet('9200000008', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000008',
            'amount' => 500,
            'method' => 'bank',
            'bank_account_holder' => 'Test User',
            'bank_account_number' => '123456789012',
            'bank_account_number_confirmation' => '123456789012',
            'bank_ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
        ])->assertSessionHasErrors('method');

        $this->assertEquals(0, WithdrawRequest::where('phone', '9200000008')->count());
    }

    public function test_amount_over_max_per_transaction_is_rejected(): void
    {
        AppSetting::set('withdrawal_method_upi_enabled', 'true');
        AppSetting::set('withdrawal_max_per_transaction', '1000');
        $user = $this->userWithWallet('9200000009', 5000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000009',
            'amount' => 2000,
            'method' => 'upi',
            'payout_upi_id' => 'test@okhdfcbank',
        ])->assertSessionHasErrors('amount');
    }

    public function test_admin_withdrawals_page_shows_method_and_destination(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'true');
        $this->userWithWallet('9200000011', 1000);

        WithdrawRequest::create([
            'phone' => '9200000011', 'amount' => 500, 'method' => 'bank',
            'bank_account_holder' => 'Jane Doe', 'bank_account_number' => '123456789012',
            'bank_ifsc' => 'HDFC0001234', 'bank_name' => 'HDFC Bank',
            'status' => WithdrawRequest::STATUS_PENDING, 'submitted_at' => now(),
        ]);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.withdrawals'))
            ->assertOk()
            ->assertSee('BANK')
            ->assertSee('Jane Doe', false);
    }

    public function test_admin_can_approve_a_bank_withdrawal_and_notification_shows_bank_destination(): void
    {
        AppSetting::set('withdrawal_method_bank_enabled', 'true');
        $user = $this->userWithWallet('9200000012', 1000);

        $withdraw = WithdrawRequest::create([
            'phone' => '9200000012', 'amount' => 500, 'method' => 'bank',
            'bank_account_holder' => 'Jane Doe', 'bank_account_number' => '123456789012',
            'bank_ifsc' => 'HDFC0001234', 'bank_name' => 'HDFC Bank',
            'status' => WithdrawRequest::STATUS_PENDING, 'submitted_at' => now(),
        ]);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.withdrawals.approve', $withdraw))
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', ['user_id' => $user->id, 'type' => 'withdrawal_approved']);
        $notification = UserNotification::where('user_id', $user->id)->where('type', 'withdrawal_approved')->firstOrFail();
        $this->assertStringContainsString('Jane Doe', $notification->body);
    }

    public function test_settings_page_requires_manage_settings_permission(): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'support'])
            ->get(route('admin.settings'))
            ->assertForbidden();
    }

    public function test_super_admin_can_save_withdrawal_method_toggles(): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.settings.update'), [
                'commission_percent' => 5,
                'max_deposit_limit' => 50000,
                'withdrawal_method_bank_enabled' => '1',
                'withdrawal_method_usdt_enabled' => '1',
            ])
            ->assertRedirect(route('admin.settings'));

        $this->assertTrue(AppSetting::enabled('withdrawal_method_bank_enabled'));
        $this->assertTrue(AppSetting::enabled('withdrawal_method_usdt_enabled'));
        $this->assertFalse(AppSetting::enabled('withdrawal_method_upi_enabled')); // omitted checkbox = off
    }

    public function test_omitting_method_defaults_to_upi_for_backward_compatibility(): void
    {
        AppSetting::set('withdrawal_method_upi_enabled', 'true');
        $user = $this->userWithWallet('9200000010', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000010',
            'amount' => 500,
            'payout_upi_id' => 'test@okhdfcbank',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('withdraw_requests', ['phone' => '9200000010', 'method' => 'upi']);
    }
}
