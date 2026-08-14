<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Client spec (item 5, Withdrawals) lists UPI Number and UPI QR as
 * "Optional" fields under UPI, and QR Code as "Optional" under USDT - these
 * were missing entirely (no column, no form field, no admin display) until
 * now. Bank was already complete; this closes the UPI/USDT gap.
 */
class WithdrawalOptionalQrFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('withdrawal_min_amount', '1');
        AppSetting::set('withdrawal_daily_limit', '50000');
        AppSetting::set('withdrawal_max_per_day', '10');
        AppSetting::set('withdrawal_max_per_transaction', '50000');
        AppSetting::set('withdrawal_method_upi_enabled', 'true');
        AppSetting::set('withdrawal_method_usdt_enabled', 'true');
    }

    private function userWithWallet(string $phone, float $balance): User
    {
        $user = User::factory()->create(['phone' => $phone]);
        WalletBalance::credit($phone, $balance);

        return $user;
    }

    public function test_upi_withdrawal_still_succeeds_without_the_optional_fields(): void
    {
        $user = $this->userWithWallet('9200000101', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000101', 'amount' => 500, 'method' => 'upi',
            'payout_upi_id' => 'test@okhdfcbank',
        ])->assertSessionHasNoErrors();

        $withdraw = WithdrawRequest::where('phone', '9200000101')->first();
        $this->assertNull($withdraw->upi_number);
        $this->assertNull($withdraw->upi_qr);
        $this->assertNull($withdraw->upiQrUrl());
    }

    public function test_upi_withdrawal_saves_optional_number_and_qr(): void
    {
        $user = $this->userWithWallet('9200000102', 1000);
        $qr = UploadedFile::fake()->image('upi-qr.jpg');

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000102', 'amount' => 500, 'method' => 'upi',
            'payout_upi_id' => 'test@okhdfcbank', 'upi_number' => '9876543210', 'upi_qr' => $qr,
        ])->assertSessionHasNoErrors();

        $withdraw = WithdrawRequest::where('phone', '9200000102')->first();
        $this->assertEquals('9876543210', $withdraw->upi_number);
        $this->assertNotNull($withdraw->upi_qr);
        $this->assertStringStartsWith('assets/withdrawal-proofs/', $withdraw->upi_qr);
        $this->assertFileExists(public_path($withdraw->upi_qr));
        $this->assertStringContainsString('9876543210', $withdraw->destinationLabel());

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.withdrawals'))
            ->assertOk()
            ->assertSee($withdraw->upiQrUrl(), false);

        @unlink(public_path($withdraw->upi_qr));
    }

    public function test_upi_number_must_be_ten_digits_when_provided(): void
    {
        $user = $this->userWithWallet('9200000103', 1000);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000103', 'amount' => 500, 'method' => 'upi',
            'payout_upi_id' => 'test@okhdfcbank', 'upi_number' => '123',
        ])->assertSessionHasErrors('upi_number');
    }

    public function test_usdt_withdrawal_saves_optional_qr(): void
    {
        $user = $this->userWithWallet('9200000104', 1000);
        $qr = UploadedFile::fake()->image('usdt-qr.jpg');

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'phone' => '9200000104', 'amount' => 500, 'method' => 'usdt',
            'usdt_address' => 'TN3W4H6rK2ce4vX9YnyKymX9YnyKymX9Yn', // valid-shape TRC20: T + 33 base58 chars
            'usdt_qr' => $qr,
        ])->assertSessionHasNoErrors();

        $withdraw = WithdrawRequest::where('phone', '9200000104')->first();
        $this->assertNotNull($withdraw->usdt_qr);
        $this->assertFileExists(public_path($withdraw->usdt_qr));
        $this->assertNotNull($withdraw->usdtQrUrl());

        @unlink(public_path($withdraw->usdt_qr));
    }
}
