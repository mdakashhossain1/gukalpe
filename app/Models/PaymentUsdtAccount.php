<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentUsdtAccount extends Model
{
    protected $fillable = [
        'usdt_address', 'display_name', 'qr_image', 'is_active', 'sort_order', 'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
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

    // qr_image is optional here (unlike UPI's required one) - a path
    // relative to public/ when set, same no-storage-symlink convention as
    // every other upload in this app.
    public function qrImageUrl(): ?string
    {
        if (! $this->qr_image) {
            return null;
        }

        return str_starts_with($this->qr_image, 'http://') || str_starts_with($this->qr_image, 'https://')
            ? $this->qr_image
            : asset($this->qr_image);
    }
}
