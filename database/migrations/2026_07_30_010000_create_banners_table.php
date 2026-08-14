<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed marketing banners (plan.md Section 34 — Banner Management).
     * Placement decides where a banner shows (home / offer / explore / popup);
     * each carries an image, an optional redirect link, an active flag, a
     * priority for ordering, and an optional start/end schedule window.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement')->default('home'); // home | offer | explore | popup
            $table->string('title')->nullable();          // admin-facing label
            $table->string('image');
            $table->string('redirect_link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
