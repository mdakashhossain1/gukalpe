<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null = active. A timestamp = banned (and when). A banned user is
            // logged out on their next request and blocked at every login path.
            $table->timestamp('banned_at')->nullable()->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('banned_at');
        });
    }
};
