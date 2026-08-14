<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unified wallet ledger (plan.md Section 30 — Transaction Management).
     * Every completed money movement writes one row here via
     * WalletBalance::credit()/debit(). Pending/failed deposit & withdrawal
     * REQUESTS still live in their own tables (deposit_requests /
     * withdraw_requests) and their admin pages; this table is the log of
     * movements that actually hit a wallet.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            // add_money, plan_purchase, profit_credit, referral_bonus,
            // cashback, withdrawal, manual_credit, manual_debit
            $table->string('type');
            $table->string('direction'); // 'credit' | 'debit'
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('success'); // success | pending | failed | cancelled
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
