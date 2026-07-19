<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanTopup extends Model
{
    protected $fillable = ['user_plan_id', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function userPlan(): BelongsTo
    {
        return $this->belongsTo(UserPlan::class);
    }
}
