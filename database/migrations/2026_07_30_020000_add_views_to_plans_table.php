<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Plan-details impression counter for Plan Analytics (plan.md Section 27).
    // Purchases are already counted in plans.total_purchases_count.
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('views')->default(0)->after('total_purchases_count');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }
};
