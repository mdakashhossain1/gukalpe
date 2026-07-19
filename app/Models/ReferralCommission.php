<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'user_plan_id',
        'amount',
        'commission_percent',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_percent' => 'decimal:2',
        ];
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
}
