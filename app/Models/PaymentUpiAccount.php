<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentUpiAccount extends Model
{
    protected $fillable = [
        'upi_id', 'display_name', 'mobile_number', 'qr_image', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
