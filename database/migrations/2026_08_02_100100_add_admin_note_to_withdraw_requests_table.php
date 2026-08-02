<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lets an admin record why a withdrawal was rejected (or approved with a
    // note) - the reject flow previously captured zero fields at all. Also
    // reused by the upcoming Bank/UPI/USDT withdrawal-methods feature.
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->string('admin_note', 500)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });
    }
};
