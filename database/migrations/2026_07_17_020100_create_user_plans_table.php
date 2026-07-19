<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A real purchase record, replacing the fake `window.purchasePlan()`
     * that only ever wrote a plan instance into the
     * `bachatpe_plans_{phone}` localStorage array. `invested_amount` and
     * `daily_profit_val` are snapshotted from the Plan at purchase time
     * (not looked up live via plan_id) so a later admin edit to a plan's
     * price/rate can never retroactively change what an existing holder
     * already bought.
     */
    public function up(): void
    {
        Schema::create('user_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->decimal('invested_amount', 12, 2);
            $table->decimal('daily_profit_val', 12, 2);
            $table->string('status')->default('active'); // active | withdrawn
            $table->timestamp('purchased_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_plans');
    }
};
