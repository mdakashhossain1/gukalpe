<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "SIP-style" mode, separate from (and off by default alongside) the
     * one-time flexible-amount slider (Plan::isFlexibleAmount()) - only
     * meaningful when that's also true. Off: a purchase always creates a
     * fresh UserPlan (today's behavior, unchanged). On: a user's first
     * contribution opens one ongoing "pot" for that plan, and every
     * subsequent contribution tops up the SAME UserPlan row (shared single
     * maturity date, cumulative total capped at max_investment_amount)
     * instead of creating a new independent purchase - see
     * PlanPurchaseController::purchase().
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allow_topups')->default(false)->after('max_investment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('allow_topups');
        });
    }
};
