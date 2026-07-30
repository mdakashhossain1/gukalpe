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
            ['label' => 'Profit credited', 'count' => null, 'amount' => $ledger('profit_credit')],
            ['label' => 'Referral bonus paid', 'count' => null, 'amount' => $ledger('referral_bonus')],
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
