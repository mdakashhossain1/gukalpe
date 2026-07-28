<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_purchases')->nullable()->after('cooldown_days');
            $table->unsignedInteger('total_purchases_count')->default(0)->after('max_purchases');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_purchases', 'total_purchases_count']);
        });
    }
};
