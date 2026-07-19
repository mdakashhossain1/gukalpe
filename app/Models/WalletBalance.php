<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletBalance extends Model
{
    protected $fillable = ['phone', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public static function balanceFor(string $phone): float
    {
        return (float) (self::where('phone', $phone)->value('balance') ?? 0);
    }

    public static function credit(string $phone, float $amount): self
    {
        $wallet = self::firstOrCreate(['phone' => $phone], ['balance' => 0]);
        $wallet->increment('balance', $amount);

        return $wallet;
    }

    // Callers must check balanceFor() >= $amount first (both at withdrawal
    // request time and again at admin approval time, since the balance can
    // change in between) - this does not re-check, so it can drive the
    // balance negative if called blindly.
    public static function debit(string $phone, float $amount): self
    {
        $wallet = self::firstOrCreate(['phone' => $phone], ['balance' => 0]);
        $wallet->decrement('balance', $amount);

        return $wallet;
    }
}
