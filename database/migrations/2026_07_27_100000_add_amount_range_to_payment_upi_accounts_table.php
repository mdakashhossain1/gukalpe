<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-account amount routing: on Add Money the shown account is now chosen by
// the deposit amount (see DepositRequestController::create) instead of a random
// pick within a single global method. Both bounds are optional - blank means
// "no lower/upper limit", so an account with neither set matches every amount.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_upi_accounts', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable()->after('mobile_number');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_upi_accounts', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount']);
        });
    }
};
