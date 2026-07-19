<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One icon per category (a Plan.badge value), now admin-editable from
     * the Plan form's category picker instead of living in the hardcoded
     * Plan::BADGE_ICONS PHP constant it replaces. A badge is shared across
     * every plan that uses it, so its icon belongs on its own row rather
     * than duplicated onto each plan.
     */
    public function up(): void
    {
        Schema::create('plan_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->default('bi-tag-fill'); // Bootstrap Icons class
            $table->timestamps();
        });

        // Carries over the exact icons that used to live in
        // Plan::BADGE_ICONS so existing plans keep the same badge icon.
        $now = now();
        DB::table('plan_categories')->insert([
            ['name' => 'Trending', 'icon' => 'bi-fire', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fast Return', 'icon' => 'bi-lightning-charge', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Beginner', 'icon' => 'bi-flower1', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Verified', 'icon' => 'bi-check-circle-fill', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_categories');
    }
};
