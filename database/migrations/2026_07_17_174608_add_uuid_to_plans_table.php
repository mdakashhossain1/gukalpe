<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Explore/PlanDetails/purchase routes bind on {plan}, which by default
     * resolves against the auto-increment `id` - exposing a sequential,
     * guessable value in the URL (and letting anyone enumerate the whole
     * catalog by walking /plan-details/1, /2, /3...). Same fix already
     * applied to DepositRequest/WithdrawRequest: a random UUID as the route
     * key, `id` stays purely internal (foreign keys on user_plans, ordering).
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('plans')->select('id')->orderBy('id')->each(function ($row) {
            DB::table('plans')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
