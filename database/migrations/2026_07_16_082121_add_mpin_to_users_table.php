<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 4-digit MPIN is the fast-login factor for returning phone users
     * (set once, right after their first OTP verification; re-set via the
     * forgot-mpin flow, which is just another OTP verification). Hashed
     * the same way `password` is - see User::casts().
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mpin')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mpin');
        });
    }
};
