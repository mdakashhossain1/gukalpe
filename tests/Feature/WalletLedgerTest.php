<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_and_debit_write_typed_ledger_rows(): void
    {
        WalletBalance::credit('9990001111', 500, 'add_money', ['deposit_id' => 7]);
        WalletBalance::debit('9990001111', 200, 'plan_purchase', ['plan' => 'X']);

        $rows = WalletTransaction::where('phone', '9990001111')->orderBy('id')->get();
        $this->assertCount(2, $rows);

        $this->assertEquals(['add_money', 'credit', '500.00', '500.00'], [
            $rows[0]->type, $rows[0]->direction, (string) $rows[0]->amount, (string) $rows[0]->balance_after,
        ]);
        $this->assertEquals(7, $rows[0]->meta['deposit_id']);

        $this->assertEquals(['plan_purchase', 'debit', '200.00', '300.00'], [
            $rows[1]->type, $rows[1]->direction, (string) $rows[1]->amount, (string) $rows[1]->balance_after,
        ]);
    }

    public function test_maturity_records_a_plan_maturity_credit_transaction(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);
        WalletBalance::firstOrCreate(['phone' => '9876543210'], ['balance' => 0]);

        $plan = Plan::create([
            'title' => 'Ledger Plan', 'subtitle' => 'Test', 'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank', 'investment_amount' => 500, 'daily_profit' => 10,
            'total_return' => 800, 'growth_rate' => 10, 'lock_duration' => '3 Months',
            'badge' => 'General', 'auto_mature' => true, 'is_active' => true,
        ]);

        UserPlan::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'invested_amount' => 500,
            'daily_profit_val' => 10, 'total_return' => null, 'duration_label' => '3 Months',
            'status' => UserPlan::STATUS_ACTIVE, 'purchased_at' => now()->subDays(30), 'matures_at' => now()->subDay(),
        ]);

        $this->artisan('plans:mature-holdings');

        $txn = WalletTransaction::where('phone', '9876543210')->where('type', 'plan_maturity_credit')->first();
        $this->assertNotNull($txn);
        $this->assertEquals('credit', $txn->direction);
        $this->assertEquals('800.00', (string) $txn->amount); // 500 invested + 300 profit
        $this->assertEquals('Ledger Plan', $txn->meta['plan']);
        $this->assertEquals(500.0, $txn->meta['invested_amount']);
        $this->assertEquals(300.0, $txn->meta['profit_amount']);
    }
}
