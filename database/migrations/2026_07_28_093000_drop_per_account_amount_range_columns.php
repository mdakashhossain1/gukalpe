<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reverts the per-account amount-range columns added earlier the same week.
// Routing is method-level, not per-account: the admin sets ONE amount range for
// UPI and ONE for Bank (stored in app_settings), and the deposit amount only
// decides which METHOD is shown - the specific account stays a random pick
// among all active accounts of that method. See DepositRequestController::create.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_upi_accounts', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount']);
        });
        Schema::table('payment_bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_upi_accounts', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable()->after('mobile_number');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
        });
        Schema::table('payment_bank_accounts', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable()->after('branch_name');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
        });
    }
};
