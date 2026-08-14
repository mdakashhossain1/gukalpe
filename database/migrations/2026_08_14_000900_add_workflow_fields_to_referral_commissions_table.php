<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Referral commissions move from "instant pay on creation" to a
     * Pending -> Paid/Rejected/Reversed admin-reviewed workflow, and gain a
     * second source (deposit, alongside the existing plan-purchase). A
     * deposit-sourced row has no user_plan_id, so that column's unique
     * constraint (the original double-credit guard) is replaced by a new
     * unique deposit_request_id for that source - each source keeps its
     * own DB-level double-credit guard.
     *
     * deposit_request_id is deliberately NOT a real constrained() foreign
     * key: SQLite can't add an enforced FK to an existing table via ALTER
     * (only at CREATE TABLE time), so the unique index - the actually
     * load-bearing guard here - is added as a standalone index instead of
     * folding it into a column ->change(), which is what actually enforces
     * the double-credit protection. Referential integrity to
     * deposit_requests is handled at the application level via the model
     * relationship, same tradeoff already accepted for a plain FK-less
     * column elsewhere in this schema when full rebuild semantics aren't
     * needed.
     *
     * Every row that already exists at migration time was created under
     * the old instant-pay model, so it's backfilled to status=paid,
     * source=plan_purchase rather than appearing as newly pending.
     */
    public function up(): void
    {
        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->dropUnique(['user_plan_id']);
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->foreignId('user_plan_id')->nullable()->change();
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->unsignedBigInteger('deposit_request_id')->nullable()->after('user_plan_id');
            $table->string('source')->nullable()->after('deposit_request_id');
            $table->string('status')->nullable()->after('commission_percent');
            $table->text('reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reason');
        });

        DB::table('referral_commissions')->select('id')->orderBy('id')->each(function ($row) {
            DB::table('referral_commissions')->where('id', $row->id)->update([
                'uuid' => (string) Str::uuid(),
                'source' => 'plan_purchase',
                'status' => 'paid',
            ]);
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->unique('deposit_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->dropUnique(['deposit_request_id']);
            $table->dropUnique(['uuid']);
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'source', 'status', 'reason', 'reviewed_at', 'deposit_request_id']);
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->foreignId('user_plan_id')->nullable(false)->unique()->change();
        });
    }
};
