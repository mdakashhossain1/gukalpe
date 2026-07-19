<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed bank accounts for the manual payment gateway's Bank
     * Transfer method - same random-rotation-on-load model as
     * payment_upi_accounts. See PaymentGatewayController.
     */
    public function up(): void
    {
        Schema::create('payment_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('ifsc_code');
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_bank_accounts');
    }
};
