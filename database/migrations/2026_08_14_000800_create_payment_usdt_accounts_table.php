<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed USDT (TRC20) collection accounts for the manual payment
     * gateway - full parity with payment_upi_accounts/payment_bank_accounts
     * (client request: same rotation/priority/amount-range behavior).
     * Unlike UPI's qr_image, the QR here is nullable - the client spec
     * explicitly marks "QR Code" as Optional for USDT (only the TRC20
     * address itself is required).
     */
    public function up(): void
    {
        Schema::create('payment_usdt_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('usdt_address');
            $table->string('display_name')->nullable();
            $table->string('qr_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_usdt_accounts');
    }
};
