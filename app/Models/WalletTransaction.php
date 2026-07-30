<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = ['phone', 'type', 'direction', 'amount', 'status', 'balance_after', 'meta'];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    // Human labels for the admin ledger, keyed by the stored type.
    public const TYPE_LABELS = [
        'add_money' => 'Add Money',
        'plan_purchase' => 'Plan Purchase',
        'profit_credit' => 'Profit Credit',
        'referral_bonus' => 'Referral Bonus',
        'cashback' => 'Cashback',
        'withdrawal' => 'Withdrawal',
        'manual_credit' => 'Manual Credit',
        'manual_debit' => 'Manual Debit',
    ];

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    // Wallets are keyed by phone (there is no user_id column); expose the
    // owning user for the admin list via the phone link.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'phone', 'phone');
    }
}
