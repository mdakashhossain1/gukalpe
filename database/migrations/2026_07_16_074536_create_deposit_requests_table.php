<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual UPI "Add Money" requests - the user pays via their own UPI app
     * to a fixed VPA outside this system, then submits the UTR here for an
     * admin to verify before the wallet is credited. See DESIGN.md's
     * "Manual UPI Add Money" section.
     */
    public function up(): void
    {
        Schema::create('deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->decimal('amount', 12, 2);
            $table->string('method'); // googlepay | phonepe | paytm | other
            $table->string('method_label');
            // A UTR is a real bank transaction reference number, so it must
            // never be usable twice for a *live* claim - but plain
            // ->unique() would also permanently block a UTR after a
            // rejection, even though a rejection usually means the *claim*
            // was wrong (typo'd amount, etc.), not that the UTR itself is
            // fraudulent. A partial unique index (below, excluding
            // 'rejected' rows) enforces uniqueness only among
            // pending/approved requests, so a genuine resubmission after a
            // rejection is still possible.
            $table->string('utr');
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // SQLite (this app's dev/prod database) supports partial indexes
        // directly. If this app ever moves to MySQL, MySQL has no partial
        // unique index equivalent - the uniqueness check would need to move
        // entirely into application logic (as the controller's validation
        // already does) with this as belt-and-suspenders only on SQLite.
        DB::statement("CREATE UNIQUE INDEX deposit_requests_utr_active_unique ON deposit_requests (utr) WHERE status != 'rejected'");
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_requests');
    }
};
