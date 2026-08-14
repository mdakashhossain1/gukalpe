<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_upi_accounts', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable()->after('sort_order');
        });
        Schema::table('payment_bank_accounts', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('payment_upi_accounts', function (Blueprint $table) {
            $table->dropColumn('last_used_at');
        });
        Schema::table('payment_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('last_used_at');
        });
    }
};
