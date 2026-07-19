<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DepositRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'phone', 'amount', 'method', 'method_label', 'utr', 'status', 'submitted_at', 'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $deposit) {
            $deposit->uuid ??= (string) Str::uuid();
        });
    }

    // The admin approve/reject routes bind on {deposit} - resolving that
    // against the auto-increment id would expose a sequential, guessable
    // value in the URL/form action. Routing on the uuid instead keeps id
    // purely internal (foreign keys, ordering) while making the value
    // that actually appears in requests unguessable.
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
