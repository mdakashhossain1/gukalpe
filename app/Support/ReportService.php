<?php

namespace App\Support;

use App\Models\DepositRequest;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use Carbon\CarbonImmutable;

/**
 * Builds the admin reports (plan.md Section 37). Aggregates are computed for a
 * date range; complete-history tables (users, deposit/withdraw requests,
 * user_plans) drive most metrics, while profit/referral come from the
 * wallet_transactions ledger (which records from its creation onward).
 */
class ReportService
{
    public const PERIODS = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ];

    /**
     * @return array{period:string,label:string,from:CarbonImmutable,to:CarbonImmutable}
     */
    public static function range(string $period): array
    {
        $now = CarbonImmutable::now();
        $period = array_key_exists($period, self::PERIODS) ? $period : 'monthly';

        $from = match ($period) {
            'daily' => $now->startOfDay(),
            'weekly' => $now->startOfWeek(),
            'yearly' => $now->startOfYear(),
            default => $now->startOfMonth(),
        };

        return [
            'period' => $period,
            'label' => self::PERIODS[$period],
            'from' => $from,
            'to' => $now,
        ];
    }

    /**
     * @return array<int, array{label:string, count:?int, amount:?float}>
     */
    public static function metrics(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $between = [$from, $to];

        $deposits = DepositRequest::where('status', DepositRequest::STATUS_APPROVED)
            ->whereBetween('submitted_at', $between);
        $withdrawals = WithdrawRequest::where('status', WithdrawRequest::STATUS_APPROVED)
            ->whereBetween('submitted_at', $between);
        $investments = UserPlan::whereBetween('purchased_at', $between);

        $ledger = fn (string $type) => (float) WalletTransaction::where('type', $type)
            ->whereBetween('created_at', $between)->sum('amount');

        return [
            ['label' => 'New users', 'count' => User::whereBetween('created_at', $between)->count(), 'amount' => null],
            ['label' => 'Deposits (approved)', 'count' => (clone $deposits)->count(), 'amount' => (float) (clone $deposits)->sum('amount')],
            ['label' => 'Investments (plan purchases)', 'count' => (clone $investments)->count(), 'amount' => (float) (clone $investments)->sum('invested_amount')],
            // 'profit_credit' rows predate 2026-08-09 (profit-only maturity payouts);
            // 'plan_maturity_credit' rows are from that date on (principal + profit -
            // see MaturePlanHoldings). Kept as two lines rather than merged so this
            // report never silently reinterprets old ledger rows' original meaning.
            ['label' => 'Profit credited (legacy, pre-2026-08-09)', 'count' => null, 'amount' => $ledger('profit_credit')],
            ['label' => 'Plan maturity payouts (principal + profit)', 'count' => null, 'amount' => $ledger('plan_maturity_credit')],
            // Same "keep legacy and current as separate lines" precedent as
            // profit_credit/plan_maturity_credit above: 'referral_bonus' rows
            // predate the Pending->Approved commission workflow (instant
            // auto-pay on plan purchase); 'referral_commission' rows are
            // admin-approved payouts from that workflow on, either source.
            ['label' => 'Referral bonus paid', 'count' => null, 'amount' => $ledger('referral_bonus')],
            ['label' => 'Referral commission paid', 'count' => null, 'amount' => $ledger('referral_commission')],
            ['label' => 'Referral commission reversed', 'count' => null, 'amount' => $ledger('referral_reversal')],
            ['label' => 'Withdrawals (approved)', 'count' => (clone $withdrawals)->count(), 'amount' => (float) (clone $withdrawals)->sum('amount')],
        ];
    }

    /**
     * @return array{period:string,label:string,from:CarbonImmutable,to:CarbonImmutable,metrics:array}
     */
    public static function build(string $period): array
    {
        $range = self::range($period);
        $range['metrics'] = self::metrics($range['from'], $range['to']);

        return $range;
    }
}
