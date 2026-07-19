<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WithdrawRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'phone', 'amount', 'payout_upi_id', 'status', 'submitted_at', 'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $withdraw) {
            $withdraw->uuid ??= (string) Str::uuid();
        });
    }

    // Same reasoning as DepositRequest::getRouteKeyName() - the admin
    // approve/reject URLs must use the opaque uuid, never the sequential id.
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
