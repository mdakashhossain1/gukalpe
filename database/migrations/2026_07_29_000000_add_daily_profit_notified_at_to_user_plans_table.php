<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            // Per-day idempotency guard for the Daily Profit Engine in-app
            // notification (plan.md Section 15/24/25) - mirrors the existing
            // last_daily_return_email_sent_at column used by the email digest.
            $table->date('last_daily_profit_notified_at')->nullable()->after('last_daily_return_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->dropColumn('last_daily_profit_notified_at');
        });
    }
};
