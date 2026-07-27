<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admins can now upload a custom icon image per plan (public/assets/plan-icons)
// instead of only picking a Bootstrap Icons class. The Explore card shows this
// icon in the circular pod alongside the separate main `image`. `icon` (the
// Bootstrap class) is kept as the fallback when no image is uploaded.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('icon_image')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('icon_image');
        });
    }
};
