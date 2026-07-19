<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generalizes "one plan, several durations, each with its own return" -
     * needed by Growth Plan's 3/6/12-month options and by the admin
     * "Multiple Duration Options (Max 4)" control. A plan with zero rows
     * here behaves exactly as before (its own lock_duration/growth_rate/
     * daily_profit/total_return columns are used as-is) - fully additive,
     * the existing 5 plans need no data migration.
     */
    public function up(): void
    {
        Schema::create('plan_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('label'); // "3 Months", "1 Day", "1 Year"
            $table->unsignedInteger('duration_days'); // numeric so maturity can be computed without parsing free text
            $table->unsignedInteger('growth_rate');
            $table->decimal('daily_profit', 12, 2);
            $table->decimal('total_return', 12, 2);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_durations');
    }
};
