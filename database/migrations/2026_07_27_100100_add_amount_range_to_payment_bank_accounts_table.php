<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mirrors the UPI table's min_amount/max_amount - the Add Money page treats
// every active account of either method as a candidate and shows one whose
// range covers the deposit amount.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_bank_accounts', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable()->after('branch_name');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount']);
        });
    }
};
