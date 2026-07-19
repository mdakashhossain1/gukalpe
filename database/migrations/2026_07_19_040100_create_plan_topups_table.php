<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-contribution audit trail for top-up-enabled plans (plans.md's
     * "Investment History") - the UserPlan row itself only ever holds the
     * current CUMULATIVE invested_amount/daily_profit_val/total_return
     * (needed for accrual/maturity math), so without this table individual
     * top-up amounts/dates would be lost the moment the next one lands.
     * One row is also written for the very first contribution that opens
     * the pot, so a pot's full contribution history is always complete
     * here, not just the top-ups after it.
     */
    public function up(): void
    {
        Schema::create('plan_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_topups');
    }
};
