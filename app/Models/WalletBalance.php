<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletBalance extends Model
{
    protected $fillable = ['phone', 'balance'];

    public static function balanceFor(string $phone): float
    {
        return (float) (self::where('phone', $phone)->value('balance') ?? 0);
    }

    public static function credit(string $phone, float $amount, string $type = 'manual_credit', array $meta = []): self
    {
        $wallet = self::firstOrCreate(['phone' => $phone], ['balance' => 0]);
        $balanceBefore = (float) $wallet->balance;
        $wallet->increment('balance', $amount);

        self::recordLedger($phone, $type, 'credit', $amount, $balanceBefore, (float) $wallet->balance, $meta);

        return $wallet;
    }

    // Callers must check balanceFor() >= $amount first (both at withdrawal
    // request time and again at admin approval time, since the balance can
    // change in between) - this does not re-check, so it can drive the
    // balance negative if called blindly.
    public static function debit(string $phone, float $amount, string $type = 'manual_debit', array $meta = []): self
    {
        $wallet = self::firstOrCreate(['phone' => $phone], ['balance' => 0]);
        $balanceBefore = (float) $wallet->balance;
        $wallet->decrement('balance', $amount);

        self::recordLedger($phone, $type, 'debit', $amount, $balanceBefore, (float) $wallet->balance, $meta);

        return $wallet;
    }

    // Writes one wallet_transactions ledger row per completed movement
    // (plan.md Section 30). Kept private so every credit/debit is logged in
    // exactly one place and no call site can move money without a ledger entry.
    private static function recordLedger(string $phone, string $type, string $direction, float $amount, float $balanceBefore, float $balanceAfter, array $meta): void
    {
        WalletTransaction::create([
            'phone' => $phone,
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'status' => 'success',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'meta' => $meta ?: null,
        ]);
    }

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }
}
