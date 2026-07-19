<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshots which duration was purchased (same "never retroactively
     * changes" principle as invested_amount/daily_profit_val) and stores a
     * precomputed maturity timestamp so the maturity scheduler can query
     * matured holdings in O(1) instead of parsing lock_duration free text.
     */
    public function up(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->foreignId('plan_duration_id')->nullable()->after('plan_id')
                ->constrained('plan_durations')->nullOnDelete();
            $table->string('duration_label')->nullable()->after('daily_profit_val');
            $table->timestamp('matures_at')->nullable()->after('purchased_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_duration_id');
            $table->dropColumn(['duration_label', 'matures_at']);
        });
    }
};
