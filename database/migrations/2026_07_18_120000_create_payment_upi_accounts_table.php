<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed UPI accounts for the manual payment gateway - one of
     * these is picked at random on every /add-money page load so
     * collections rotate across accounts instead of hitting a single
     * hardcoded VPA. See app/Modules/Admin/Controllers/PaymentGatewayController.php.
     */
    public function up(): void
    {
        Schema::create('payment_upi_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('upi_id');
            $table->string('display_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('qr_image');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_upi_accounts');
    }
};
