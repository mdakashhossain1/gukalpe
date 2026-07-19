<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Both nullable and both left unset by default, so every existing plan
     * (including Trust Builder/Growth, explicitly "(Fixed)" per plans.md)
     * keeps its current single-amount behavior untouched. A plan only
     * becomes "flexible amount" (real drag-slider on Plan Details, amount
     * chosen per purchase instead of always plan.investment_amount) when an
     * admin sets both AND max > min - see Plan::isFlexibleAmount().
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('min_investment_amount', 12, 2)->nullable()->after('investment_amount');
            $table->decimal('max_investment_amount', 12, 2)->nullable()->after('min_investment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['min_investment_amount', 'max_investment_amount']);
        });
    }
};
