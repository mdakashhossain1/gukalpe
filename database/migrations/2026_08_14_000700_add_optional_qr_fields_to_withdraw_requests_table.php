<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client spec (item 5, Withdrawals) lists these as the full field set per
 * method - Bank was already complete, but UPI/USDT were missing their
 * explicitly-"Optional" fields:
 *   UPI:  UPI ID (have), UPI Number (missing), UPI QR (missing)
 *   USDT: TRC20 Address (have), QR Code (missing)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->string('upi_number', 10)->nullable()->after('payout_upi_id');
            $table->string('upi_qr')->nullable()->after('upi_number');
            $table->string('usdt_qr')->nullable()->after('usdt_address');
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropColumn(['upi_number', 'upi_qr', 'usdt_qr']);
        });
    }
};
