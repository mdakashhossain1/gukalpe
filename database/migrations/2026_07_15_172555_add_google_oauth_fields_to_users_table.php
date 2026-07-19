<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->string('avatar')->nullable()->after('email');
            // Links a real (Google-authenticated) user to the existing
            // client-side-simulated user record the rest of the app reads
            // (bachatpe_wallet_balance_{phone}, gullakpe_users, etc. in
            // localStorage) - see MEMORY.md's "no database yet" notes. Not
            // required at signup (Google gives no phone number), filled in
            // once the user completes the existing phone/OTP step.
            $table->string('phone', 20)->nullable()->unique()->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar', 'phone']);
        });
    }
};
