<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the Trust Builder / Growth Plan unlock system plus the premium
     * plan-page wishlist's admin controls (risk level, slots, schedule,
     * terms/FAQs, highlight chips) as real Plan columns - every column is
     * nullable/defaulted so the existing 5 plans are unaffected.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // 'trust_builder' | 'growth' | null - lets code find "the"
            // Trust Builder/Growth plan by type instead of matching title text.
            $table->string('plan_type')->nullable()->after('badge');

            $table->unsignedInteger('max_purchase_per_user')->nullable()->after('sort_order');
            $table->unsignedInteger('cooldown_days')->nullable()->after('max_purchase_per_user');

            $table->foreignId('requires_plan_id')->nullable()->after('cooldown_days')
                ->constrained('plans')->nullOnDelete();
            $table->boolean('unlock_enabled')->default(false)->after('requires_plan_id');
            $table->text('unlock_message')->nullable()->after('unlock_enabled');

            $table->string('marketing_badge')->nullable()->after('unlock_message');
            $table->string('risk_level')->nullable()->after('marketing_badge'); // Low | Medium | High

            $table->unsignedInteger('max_slots')->nullable()->after('risk_level');
            $table->timestamp('start_date')->nullable()->after('max_slots');
            $table->timestamp('end_date')->nullable()->after('start_date');

            $table->boolean('auto_mature')->default(true)->after('end_date');
            $table->boolean('early_close_allowed')->default(false)->after('auto_mature');

            $table->longText('terms')->nullable()->after('early_close_allowed');
            $table->json('faqs')->nullable()->after('terms'); // [{q, a}, ...]
            $table->json('highlights')->nullable()->after('faqs'); // ["24x7 Support", ...]
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requires_plan_id');
            $table->dropColumn([
                'plan_type', 'max_purchase_per_user', 'cooldown_days',
                'unlock_enabled', 'unlock_message', 'marketing_badge', 'risk_level',
                'max_slots', 'start_date', 'end_date', 'auto_mature', 'early_close_allowed',
                'terms', 'faqs', 'highlights',
            ]);
        });
    }
};
