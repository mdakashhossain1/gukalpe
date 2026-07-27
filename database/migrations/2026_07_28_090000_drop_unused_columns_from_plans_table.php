<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Removes three plan fields that the current UI/purchase flow never reads
// (confirmed unused): `max_slots` (availableSlots() was never called and the
// purchase flow never checked capacity), `early_close_allowed` (no early-close
// flow exists), and `min_goal` (never rendered on any user-facing screen).
// Simplifies the admin plan form - see MEMORY.md 2026-07-28.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_slots', 'early_close_allowed', 'min_goal']);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_slots')->nullable()->after('risk_level');
            $table->boolean('early_close_allowed')->default(false)->after('auto_mature');
            $table->decimal('min_goal', 12, 2)->default(0)->after('allow_topups');
        });
    }
};
