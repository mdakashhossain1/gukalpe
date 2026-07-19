<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per commission ever paid. The unique constraint on
     * user_plan_id means a given plan purchase can only ever produce one
     * commission row - the real guard against double-crediting, on top of
     * the "first purchase only" check in application code.
     */
    public function up(): void
    {
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_plan_id')->unique()->constrained('user_plans')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->timestamps();

            $table->index('referrer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
