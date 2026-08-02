<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Authoritative day-count for the plan-level auto profit formula when a
    // plan has no duration rows - the admin form already computed this
    // client-side (#term_days_calc) but never persisted it, which is why
    // daily_profit/total_return had to stay admin-editable until now.
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('term_days')->nullable()->after('growth_rate');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('term_days');
        });
    }
};
