<?php

namespace Tests\Feature;

use App\Models\DepositRequest;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use App\Support\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function metric(array $report, string $label): array
    {
        foreach ($report['metrics'] as $m) {
            if ($m['label'] === $label) {
                return $m;
            }
        }
        $this->fail("Metric '{$label}' not found");
    }

    public function test_monthly_report_aggregates_only_in_range_records(): void
    {
        $inRange = now();               // this month
        $outOfRange = now()->subMonths(2);

        // Users: 2 this month, 1 older
        $u1 = User::factory()->create(['phone' => '9990000001', 'created_at' => $inRange]);
        User::factory()->create(['phone' => '9990000002', 'created_at' => $inRange]);
        User::factory()->create(['phone' => '9990000003', 'created_at' => $outOfRange]);

        // Deposits: 1 approved in range (₹1000), 1 pending (excluded), 1 approved but old
        $dep = ['phone' => '9990000001', 'method' => 'upi', 'method_label' => 'UPI'];
        DepositRequest::create([...$dep, 'utr' => 'X1', 'amount' => 1000, 'status' => 'approved', 'submitted_at' => $inRange]);
        DepositRequest::create([...$dep, 'utr' => 'X2', 'amount' => 500, 'status' => 'pending', 'submitted_at' => $inRange]);
        DepositRequest::create([...$dep, 'utr' => 'X3', 'amount' => 999, 'status' => 'approved', 'submitted_at' => $outOfRange]);

        // Investments: 2 this month (₹500 + ₹300)
        $plan = Plan::create([
            'title' => 'Rpt Plan', 'subtitle' => 't', 'image' => 'a.jpg', 'icon' => 'bi-piggy-bank',
            'investment_amount' => 500, 'daily_profit' => 5, 'total_return' => 800, 'growth_rate' => 10,
            'lock_duration' => '1 Day', 'badge' => 'General', 'is_active' => true,
        ]);
        foreach ([500, 300] as $amt) {
            UserPlan::create([
                'user_id' => $u1->id, 'plan_id' => $plan->id, 'invested_amount' => $amt,
                'daily_profit_val' => 5, 'duration_label' => '1 Day', 'status' => 'active',
                'purchased_at' => $inRange, 'matures_at' => now()->addDay(),
            ]);
        }

        // Ledger: profit credited ₹200, referral ₹50 (both this month)
        WalletTransaction::create(['phone' => '9990000001', 'type' => 'profit_credit', 'direction' => 'credit', 'amount' => 200, 'created_at' => $inRange]);
        WalletTransaction::create(['phone' => '9990000001', 'type' => 'referral_bonus', 'direction' => 'credit', 'amount' => 50, 'created_at' => $inRange]);

        // Withdrawals: 1 approved ₹400 in range
        WithdrawRequest::create(['phone' => '9990000001', 'amount' => 400, 'payout_upi_id' => 'x@ybank', 'status' => 'approved', 'submitted_at' => $inRange]);

        $report = ReportService::build('monthly');

        $this->assertEquals(2, $this->metric($report, 'New users')['count']);
        $this->assertEquals(1, $this->metric($report, 'Deposits (approved)')['count']);
        $this->assertEquals(1000.0, $this->metric($report, 'Deposits (approved)')['amount']);
        $this->assertEquals(2, $this->metric($report, 'Investments (plan purchases)')['count']);
        $this->assertEquals(800.0, $this->metric($report, 'Investments (plan purchases)')['amount']);
        $this->assertEquals(200.0, $this->metric($report, 'Profit credited (legacy, pre-2026-08-09)')['amount']);
        $this->assertEquals(50.0, $this->metric($report, 'Referral bonus paid')['amount']);
        $this->assertEquals(400.0, $this->metric($report, 'Withdrawals (approved)')['amount']);
    }

    public function test_range_defaults_and_period_selection(): void
    {
        $this->assertEquals('monthly', ReportService::range('nonsense')['period']);
        $this->assertEquals('daily', ReportService::range('daily')['period']);
        $this->assertTrue(ReportService::range('daily')['from']->isToday());
    }
}
