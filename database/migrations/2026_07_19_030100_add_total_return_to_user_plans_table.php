<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the promised total return in rupees, computed at purchase
     * time from (chosen amount) x (duration's growth_rate) - needed only
     * for flexible-amount purchases, where the shared plan_durations row's
     * own total_return is calibrated for a *different* reference amount and
     * can't be reused as this holding's accrual cap. Left null for
     * fixed-amount purchases, which keep resolving their cap from
     * planDuration/plan as before (see UserPlan::currentHolding()).
     */
    public function up(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->decimal('total_return', 12, 2)->nullable()->after('daily_profit_val');
        });
    }

    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->dropColumn('total_return');
        });
    }
};
