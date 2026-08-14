<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\DepositRequest;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_ban_requires_a_reason_and_is_recorded_in_the_audit_log(): void
    {
        $user = User::factory()->create(['phone' => '9990005555']);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.users.toggle-ban', $user), [])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($user->fresh()->isBanned());

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.users.toggle-ban', $user), ['reason' => 'Fraudulent deposit claims'])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->isBanned());
        $this->assertEquals('Fraudulent deposit claims', $user->ban_reason);

        $entry = AdminAuditLog::where('action', 'user_banned')->where('target_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals('Fraudulent deposit claims', $entry->reason);
        $this->assertEquals('Master Admin', $entry->admin_label);
    }

    public function test_unban_does_not_require_a_reason_and_clears_ban_reason(): void
    {
        $user = User::factory()->create(['phone' => '9990006666', 'banned_at' => now()]);
        $user->ban_reason = 'Old reason';
        $user->save();

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.users.toggle-ban', $user), [])
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->isBanned());
        $this->assertNull($user->ban_reason);
    }

    public function test_wallet_adjustment_captures_balance_before_and_writes_audit_log(): void
    {
        User::factory()->create(['phone' => '9990007777']);
        WalletBalance::credit('9990007777', 200, 'add_money');

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.wallet-tools.adjust'), [
                'phone' => '9990007777',
                'direction' => 'increase',
                'amount' => 50,
                'reason' => 'Loyalty bonus',
            ])
            ->assertRedirect();

        $txn = WalletTransaction::where('phone', '9990007777')->where('type', 'manual_credit')->first();
        $this->assertEquals(200.00, (float) $txn->balance_before);
        $this->assertEquals(250.00, (float) $txn->balance_after);

        $entry = AdminAuditLog::where('action', 'wallet_adjustment')->first();
        $this->assertNotNull($entry);
        $this->assertEquals('Loyalty bonus', $entry->reason);
        $this->assertEquals(200.0, $entry->meta['balance_before']);
        $this->assertEquals(250.0, $entry->meta['balance_after']);
    }

    public function test_wallet_adjustment_reason_balance_before_and_admin_are_visible_on_transactions_page(): void
    {
        User::factory()->create(['phone' => '9990007778']);
        WalletBalance::credit('9990007778', 100, 'add_money');

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.wallet-tools.adjust'), [
                'phone' => '9990007778',
                'direction' => 'increase',
                'amount' => 25,
                'reason' => 'Visible on transactions page test',
            ])
            ->assertRedirect();

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.transactions'))
            ->assertOk()
            ->assertSee('Visible on transactions page test')
            ->assertSee('Master Admin')
            ->assertSee('₹100.00', false)
            ->assertSee('₹125.00', false);
    }

    public function test_deposit_can_be_rejected_with_an_optional_reason(): void
    {
        $deposit = DepositRequest::create([
            'phone' => '9990008888', 'amount' => 300, 'method' => 'googlepay', 'method_label' => 'Google Pay',
            'utr' => 'UTR123456', 'status' => DepositRequest::STATUS_PENDING, 'submitted_at' => now(),
        ]);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.deposits.reject', $deposit), ['admin_note' => 'Amount mismatch'])
            ->assertRedirect();

        $deposit->refresh();
        $this->assertEquals(DepositRequest::STATUS_REJECTED, $deposit->status);
        $this->assertEquals('Amount mismatch', $deposit->admin_note);

        $entry = AdminAuditLog::where('action', 'deposit_rejected')->first();
        $this->assertNotNull($entry);
        $this->assertEquals('Amount mismatch', $entry->reason);
    }

    public function test_user_profile_page_shows_a_dedicated_transactions_section(): void
    {
        $user = User::factory()->create(['phone' => '9990009998']);
        WalletBalance::credit('9990009998', 300, 'add_money');

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.wallet-tools.adjust'), [
                'phone' => '9990009998',
                'direction' => 'increase',
                'amount' => 40,
                'reason' => 'Profile transactions section test',
            ])
            ->assertRedirect();

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('id="transactions"', false)
            ->assertSee('Profile transactions section test')
            ->assertSee('Master Admin')
            ->assertSee('₹340.00', false);
    }

    public function test_user_profile_page_loads_for_authenticated_admin(): void
    {
        $user = User::factory()->create(['phone' => '9990009999']);
        WalletBalance::credit('9990009999', 500, 'add_money');

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee($user->phone)
            ->assertSee('₹500.00', false);
    }

    public function test_audit_log_page_lists_recorded_entries(): void
    {
        $user = User::factory()->create(['phone' => '9990001111']);
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.users.toggle-ban', $user), ['reason' => 'Test reason']);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.audit-log'))
            ->assertOk()
            ->assertSee('Test reason');
    }
}
