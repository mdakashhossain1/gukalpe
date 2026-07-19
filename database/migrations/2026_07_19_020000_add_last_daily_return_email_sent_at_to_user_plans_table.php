<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks the last calendar day a "your daily return landed" email was
     * sent for this specific holding (plans:send-daily-returns-email) - a
     * date, not a datetime, so "already sent today" is a simple equality
     * check regardless of what time the scheduled command runs at.
     */
    public function up(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->date('last_daily_return_email_sent_at')->nullable()->after('matures_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->dropColumn('last_daily_return_email_sent_at');
        });
    }
};
