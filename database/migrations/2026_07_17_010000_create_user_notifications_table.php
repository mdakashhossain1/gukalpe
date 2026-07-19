<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * End-user notification feed (the Home page bell) - a separate table
     * from admin_notifications, which feeds the admin panel's own bell for
     * a different audience (operator-facing events like "new signup").
     * Not Laravel's built-in database-notifications system either (that
     * needs a UUID-keyed `notifications` table matched to the Notifiable
     * trait's expected schema) - this is a simpler, purpose-built feed:
     * one row per (user, event), fanned out at send time rather than a
     * single broadcast row shared across users, so read state is always
     * correct per-recipient.
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // deposit_approved | deposit_rejected | withdrawal_approved | withdrawal_rejected | admin_broadcast
            $table->string('title');
            $table->string('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
