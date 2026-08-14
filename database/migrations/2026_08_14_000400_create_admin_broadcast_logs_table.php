<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->string('target_description');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('sent_by');
            $table->string('status')->default('sent');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_broadcast_logs');
    }
};
