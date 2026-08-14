<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReferralCommission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVERSED = 'reversed';

    public const SOURCE_PLAN_PURCHASE = 'plan_purchase';

    public const SOURCE_DEPOSIT = 'deposit';

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'user_plan_id',
        'deposit_request_id',
        'source',
        'amount',
        'commission_percent',
        'status',
        'reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $commission) {
            $commission->uuid ??= (string) Str::uuid();
        });
    }

    // Admin approve/reject/adjust/reverse routes bind on {referralCommission}
    // - route on the uuid rather than the auto-increment id so the value
    // that appears in requests isn't sequential/guessable, same convention
    // as DepositRequest/WithdrawRequest.
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function userPlan(): BelongsTo
    {
        return $this->belongsTo(UserPlan::class);
    }

    public function depositRequest(): BelongsTo
    {
        return $this->belongsTo(DepositRequest::class);
    }
}
