<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // plan.md Section 10: Draft/Active/Hidden/Expired (Out Of Stock stays
    // dynamic - isOutOfStock() - it's a derived fact, not an admin-set
    // status). is_active is kept as-is (the purchase-eligibility gate,
    // untouched by this migration) - status is the new, more granular
    // catalog-visibility concept layered on top of it.
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active');
        });

        // Backfill every existing plan into a sane bucket so nothing
        // silently disappears post-deploy: currently-active plans become
        // 'active', currently-disabled plans become 'draft' (the closest
        // existing equivalent - not listed, not purchasable).
        DB::table('plans')->where('is_active', true)->update(['status' => 'active']);
        DB::table('plans')->where('is_active', false)->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
