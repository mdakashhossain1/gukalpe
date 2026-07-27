<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentUpiAccount extends Model
{
    protected $fillable = [
        'upi_id', 'display_name', 'mobile_number', 'qr_image', 'min_amount', 'max_amount', 'is_active', 'sort_order',
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

    // Amount-range routing (see DepositRequestController::create): an account is
    // a candidate for a given deposit amount when the amount sits inside its
    // [min_amount, max_amount] window. A null bound means that side is open, so
    // an account with both bounds null matches every amount.
    public function scopeCoversAmount(Builder $query, float $amount): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount))
            ->where(fn ($q) => $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount));
    }

    // qr_image is always a path relative to public/ (saved by
    // PaymentGatewayController straight into public/assets/payment-qr, same
    // no-storage-symlink convention as Plan::imageUrl()) - kept as a method
    // rather than a bare asset() call at call sites in case that ever
    // changes.
    public function qrImageUrl(): string
    {
        return str_starts_with($this->qr_image, 'http://') || str_starts_with($this->qr_image, 'https://')
            ? $this->qr_image
            : asset($this->qr_image);
    }
}
