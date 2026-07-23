<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets an admin give the marketing badge (e.g. "Recommended") a real
     * Bootstrap Icon and a colour scheme instead of the icon having to be
     * a unicode emoji typed into the marketing_badge text itself.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('marketing_badge_icon')->nullable()->after('marketing_badge');
            $table->string('marketing_badge_color', 20)->nullable()->after('marketing_badge_icon');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['marketing_badge_icon', 'marketing_badge_color']);
        });
    }
};
