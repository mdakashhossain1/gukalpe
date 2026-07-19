<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real investment-plan catalog, replacing the hardcoded `plansData`
     * object in resources/js/modules/animations.js - that object (and the
     * literal onclick(title, icon, rate, badge) calls scattered across
     * Explore/Home) was the only place these 5 plans were ever defined, so
     * an admin could not add, edit, or disable one without a code change.
     * Only the core numeric/text fields are stored; the old object's
     * redundant derived strings (expectedGrowth, planInvestment,
     * dailyProfit, totalReturn, minGoal, expectedReturn, unlockDate,
     * timelineEnd - several of which just duplicated another field
     * verbatim) are recomputed from these at read time
     * (Plan::toLegacyArray()) so the existing frontend JS keeps consuming
     * the exact same shape unchanged.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('subtitle');
            $table->string('image');
            $table->string('icon'); // Bootstrap Icons class, e.g. "bi-piggy-bank"
            $table->string('badge'); // Beginner | Trending | Fast Return | Verified
            $table->unsignedInteger('growth_rate'); // "Up to N% yearly"
            $table->string('lock_duration'); // "Flexible" | "12 Months" | "36 Months"
            $table->decimal('investment_amount', 12, 2);
            $table->decimal('daily_profit', 12, 2);
            $table->decimal('total_return', 12, 2);
            $table->decimal('min_goal', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
