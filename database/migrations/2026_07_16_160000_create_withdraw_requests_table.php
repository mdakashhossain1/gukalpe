<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual UPI withdrawal requests - the reverse of deposit_requests
     * (app/Modules/Deposits): the user asks to cash out from their wallet to
     * their own UPI ID, an admin reviews and manually sends the money
     * outside this system, then approves (debiting the wallet) or rejects.
     *
     * `uuid` is the route key from the start (see SECURITY.md's "Deposit
     * request IDs" section) rather than something added after the fact -
     * the admin approve/reject URLs must never expose the auto-increment id.
     */
    public function up(): void
    {
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('phone')->index();
            $table->decimal('amount', 12, 2);
            $table->string('payout_upi_id');
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};
