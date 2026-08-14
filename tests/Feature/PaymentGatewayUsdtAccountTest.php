<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\PaymentUsdtAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * USDT as a full third deposit-collection method alongside UPI/Bank - added
 * after the client explicitly asked for parity ("Bank/UPI/USDT are separate
 * methods"), confirmed via AskUserQuestion to mean full CRUD + rotation, not
 * just a single shared address.
 */
class PaymentGatewayUsdtAccountTest extends TestCase
{
    use RefreshDatabase;

    private array $adminSession = ['admin_authenticated' => true, 'admin_role' => 'super_admin'];

    private const VALID_ADDRESS = 'TN3W4H6rK2ce4vX9YnyKymX9YnyKymX9Yn'; // T + 33 base58 chars

    public function test_payment_gateway_index_and_usdt_form_pages_render(): void
    {
        $account = PaymentUsdtAccount::create(['usdt_address' => self::VALID_ADDRESS, 'is_active' => true]);

        $this->withSession($this->adminSession)
            ->get(route('admin.payment-gateway'))
            ->assertOk()
            ->assertSee('USDT accounts')
            ->assertSee('USDT range')
            ->assertSee(self::VALID_ADDRESS);

        $this->withSession($this->adminSession)
            ->get(route('admin.payment-gateway.usdt-accounts.create'))
            ->assertOk();

        $this->withSession($this->adminSession)
            ->get(route('admin.payment-gateway.usdt-accounts.edit', $account))
            ->assertOk()
            ->assertSee(self::VALID_ADDRESS);
    }

    public function test_admin_can_add_edit_toggle_and_delete_a_usdt_account(): void
    {
        $this->withSession($this->adminSession)
            ->post(route('admin.payment-gateway.usdt-accounts.store'), [
                'usdt_address' => self::VALID_ADDRESS,
                'display_name' => 'Main USDT wallet',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.payment-gateway'));

        $account = PaymentUsdtAccount::where('usdt_address', self::VALID_ADDRESS)->firstOrFail();
        $this->assertTrue($account->is_active);
        $this->assertNotNull(AdminAuditLog::where('action', 'usdt_account_created')->where('target_id', $account->id)->first());

        $this->withSession($this->adminSession)
            ->post(route('admin.payment-gateway.usdt-accounts.update', $account), [
                'usdt_address' => self::VALID_ADDRESS,
                'display_name' => 'Renamed wallet',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.payment-gateway'));
        $this->assertEquals('Renamed wallet', $account->fresh()->display_name);

        $this->withSession($this->adminSession)
            ->post(route('admin.payment-gateway.usdt-accounts.toggle-active', $account))
            ->assertRedirect(route('admin.payment-gateway'));
        $this->assertFalse($account->fresh()->is_active);

        $this->withSession($this->adminSession)
            ->post(route('admin.payment-gateway.usdt-accounts.delete', $account))
            ->assertRedirect(route('admin.payment-gateway'));
        $this->assertDatabaseMissing('payment_usdt_accounts', ['id' => $account->id]);
    }

    public function test_usdt_account_requires_a_valid_trc20_address(): void
    {
        $this->withSession($this->adminSession)
            ->post(route('admin.payment-gateway.usdt-accounts.store'), [
                'usdt_address' => 'not-a-real-address',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('usdt_address');
    }

    public function test_usdt_is_disabled_by_default_even_with_an_active_account(): void
    {
        PaymentUsdtAccount::create(['usdt_address' => self::VALID_ADDRESS, 'is_active' => true]);
        // usdt_min_amount/usdt_max_amount both default to '' (disabled),
        // matching Bank's off-by-default pattern - admin must opt in.
        AppSetting::set('upi_min_amount', '');
        AppSetting::set('upi_max_amount', '');
        AppSetting::set('bank_min_amount', '');
        AppSetting::set('bank_max_amount', '');

        $user = User::factory()->create(['phone' => '9990005001']);

        $this->actingAs($user)
            ->withSession(['deposit_amount_prefill' => 500])
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertSee('temporarily unavailable');
    }

    public function test_deposit_page_shows_usdt_when_amount_is_in_range(): void
    {
        PaymentUsdtAccount::create(['usdt_address' => self::VALID_ADDRESS, 'display_name' => 'Main wallet', 'is_active' => true]);
        AppSetting::set('usdt_min_amount', '0');
        AppSetting::set('usdt_max_amount', '');
        AppSetting::set('upi_min_amount', '');
        AppSetting::set('upi_max_amount', '');
        AppSetting::set('bank_min_amount', '');
        AppSetting::set('bank_max_amount', '');

        $user = User::factory()->create(['phone' => '9990005002']);

        $this->actingAs($user)
            ->withSession(['deposit_amount_prefill' => 500])
            ->get(route('deposits.create'))
            ->assertOk()
            ->assertSee('Send USDT (TRC20)')
            ->assertSee(self::VALID_ADDRESS);
    }

    public function test_usdt_deposit_submission_requires_a_transaction_hash_and_saves_correctly(): void
    {
        $user = User::factory()->create(['phone' => '9990005003']);

        $this->actingAs($user)->post(route('deposits.store'), [
            'amount' => 500, 'method' => 'usdt', 'utr' => 'too-short',
        ])->assertSessionHasErrors('utr');

        $validTxid = str_repeat('a1b2', 16); // 64 hex chars, realistic TRC20 txid length

        $this->actingAs($user)->post(route('deposits.store'), [
            'amount' => 500, 'method' => 'usdt', 'utr' => $validTxid,
        ])->assertSessionHasNoErrors();

        $deposit = DepositRequest::where('phone', '9990005003')->first();
        $this->assertEquals('usdt', $deposit->method);
        $this->assertEquals($validTxid, $deposit->utr);
        $this->assertStringContainsString('USDT', $deposit->method_label);
    }
}
