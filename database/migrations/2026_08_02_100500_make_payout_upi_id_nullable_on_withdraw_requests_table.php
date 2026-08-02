<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // payout_upi_id was NOT NULL from when UPI was the only method - now
    // that bank/USDT withdrawals exist and never populate this column, it
    // must be nullable. Bank/USDT rows simply leave it null.
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->string('payout_upi_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->string('payout_upi_id')->nullable(false)->change();
        });
    }
};
