<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // plan.md's Flexible Investment Plan spec (Section: Investment Range)
    // calls for an admin-configurable "Slider Step" - Plan Details previously
    // always auto-computed a step of (max-min)/50 with no way to override it.
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('slider_step', 12, 2)->nullable()->after('max_investment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('slider_step');
        });
    }
};
