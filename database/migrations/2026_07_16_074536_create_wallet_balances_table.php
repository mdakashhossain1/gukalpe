<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phone-keyed rather than user_id-keyed: phone/OTP login in this app is
     * entirely client-side (no real Laravel session for it, only Google
     * OAuth users get a real Auth::user()), so "phone number" is the only
     * identity this table can key on without a much larger auth rework.
     * See DESIGN.md's "Manual UPI Add Money" section for the full picture,
     * including that this table is authoritative only for deposit-driven
     * and admin-adjusted balances - it is intentionally separate from the
     * pre-existing localStorage-based wallet balance the rest of the app
     * (rewards claims, referral commission, Home's balance display) still
     * uses; unifying those was out of scope for this change.
     */
    public function up(): void
    {
        Schema::create('wallet_balances', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_balances');
    }
};
