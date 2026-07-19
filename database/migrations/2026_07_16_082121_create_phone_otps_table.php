<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per phone attempting verification (upserted on each
     * send/resend). `otp_hash` is hashed the same way passwords are -
     * never stored in plaintext even though it's only a 6-digit code.
     * `attempts` powers a lockout after too many wrong guesses, same
     * spirit as the admin login's progressive lockout
     * (see App\Modules\Admin\Controllers\AdminController).
     */
    public function up(): void
    {
        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_otps');
    }
};
