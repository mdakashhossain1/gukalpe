<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // plan2.md's new withdrawal-methods section: Bank Account / UPI /
    // USDT(TRC20) as selectable methods. payout_upi_id is kept exactly as-is
    // (not renamed) - every existing row defaults to method='upi' and stays
    // meaningful with zero backfill. Method-specific columns are simply null
    // for non-matching methods.
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->string('method')->default('upi')->after('amount');
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('usdt_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropColumn([
                'method', 'bank_account_holder', 'bank_account_number',
                'bank_ifsc', 'bank_name', 'bank_branch', 'usdt_address',
            ]);
        });
    }
};
