<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feed for the admin panel's notification bell (polled via AJAX, see
     * app/Modules/Admin - "Ops console"). Single-admin tool, so there is no
     * per-recipient tracking: `read_at` is set (once) when the bell is
     * opened, exactly like a single shared inbox.
     */
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // user_registered | withdrawal_request
            $table->string('title');
            $table->string('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
