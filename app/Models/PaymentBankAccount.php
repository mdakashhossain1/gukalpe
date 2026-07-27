<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentBankAccount extends Model
{
    protected $fillable = [
        'account_holder_name', 'account_number', 'ifsc_code', 'bank_name', 'branch_name', 'min_amount', 'max_amount', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $account) {
            $account->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // Amount-range routing (see DepositRequestController::create): matches when
    // the deposit amount falls inside this account's [min_amount, max_amount]
    // window. A null bound is open on that side; both null matches everything.
    public function scopeCoversAmount(Builder $query, float $amount): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount))
            ->where(fn ($q) => $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount));
    }
}
