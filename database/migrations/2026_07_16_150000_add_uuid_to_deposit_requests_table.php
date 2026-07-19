<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The admin approve/reject routes bind on this model directly
     * (Route::post('/deposits/{deposit}/...')), which by default resolves
     * against the auto-increment `id` - exposing a sequential, guessable
     * value in the URL. A random UUID as the route key removes that
     * enumeration surface without touching the `id` used everywhere
     * internally (foreign keys, ordering, logging).
     */
    public function up(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('deposit_requests')->select('id')->orderBy('id')->each(function ($row) {
            DB::table('deposit_requests')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
