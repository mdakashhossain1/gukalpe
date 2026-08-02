<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAdjustReasonAndWithdrawalRejectNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_adjustment_requires_a_reason(): void
    {
        User::factory()->create(['phone' => '9990002222']);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.wallet-tools.adjust'), [
                'phone' => '9990002222',
                'direction' => 'increase',
                'amount' => 100,
                // reason deliberately omitted
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('wallet_transactions', ['phone' => '9990002222']);
    }

    public function test_wallet_adjustment_reason_is_recorded_in_the_ledger(): void
    {
        User::factory()->create(['phone' => '9990002222']);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.wallet-tools.adjust'), [
                'phone' => '9990002222',
                'direction' => 'increase',
                'amount' => 100,
                'reason' => 'Promotion bonus',
            ])
            ->assertRedirect();

        $txn = WalletTransaction::where('phone', '9990002222')->where('type', 'manual_credit')->first();
        $this->assertNotNull($txn);
        $this->assertEquals('Promotion bonus', $txn->meta['reason']);
    }

    public function test_withdrawal_reject_note_is_optional_and_persisted(): void
    {
        $user = User::factory()->create(['phone' => '9990003333']);
        WalletBalance::credit('9990003333', 1000, 'add_money');

        $withdraw = WithdrawRequest::create([
            'phone' => '9990003333', 'amount' => 500, 'payout_upi_id' => 'test@okhdfcbank',
            'status' => WithdrawRequest::STATUS_PENDING, 'submitted_at' => now(),
        ]);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.withdrawals.reject', $withdraw), [
                'admin_note' => 'Suspicious activity on account',
            ])
            ->assertRedirect();

        $withdraw->refresh();
        $this->assertEquals(WithdrawRequest::STATUS_REJECTED, $withdraw->status);
        $this->assertEquals('Suspicious activity on account', $withdraw->admin_note);
    }

    public function test_withdrawal_can_still_be_rejected_with_no_note(): void
    {
        WalletBalance::credit('9990004444', 1000, 'add_money');

        $withdraw = WithdrawRequest::create([
            'phone' => '9990004444', 'amount' => 500, 'payout_upi_id' => 'test@okhdfcbank',
            'status' => WithdrawRequest::STATUS_PENDING, 'submitted_at' => now(),
        ]);

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->post(route('admin.withdrawals.reject', $withdraw), [])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(WithdrawRequest::STATUS_REJECTED, $withdraw->fresh()->status);
    }
}
