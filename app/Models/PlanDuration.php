<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDuration extends Model
{
    protected $fillable = [
        'plan_id', 'label', 'duration_days', 'growth_rate',
        'daily_profit', 'total_return', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'daily_profit' => 'decimal:2',
        'total_return' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Same "purchased_at-would-be" projection used by the calculator/portfolio
    // preview on Plan Details - a maturity date as if purchased right now.
    public function projectedMaturityDate(): \Illuminate\Support\Carbon
    {
        return now()->addDays($this->duration_days);
    }
}
