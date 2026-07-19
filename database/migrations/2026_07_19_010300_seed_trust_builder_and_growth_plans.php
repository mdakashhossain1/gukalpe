<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Creates the real Trust Builder Plan (₹199, 1 day, one-time-per-user,
     * locked behind Growth Plan) and Growth Plan (₹499, 3/6/12-month
     * duration options) rows described in plans.md - same pattern as the
     * original 5-plan seed in create_plans_table's follow-up work. Return
     * numbers below are illustrative starting values; admins can edit
     * every figure afterwards via the Plan admin form (Phase 5).
     */
    public function up(): void
    {
        $now = now();

        DB::table('plan_categories')->insertOrIgnore([
            ['name' => 'Starter', 'icon' => 'bi-shield-check', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Growth', 'icon' => 'bi-graph-up-arrow', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $growthPlanId = DB::table('plans')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'title' => 'Growth Plan',
            'subtitle' => 'Steady returns over 3, 6, or 12 months - unlocks the Trust Builder Plan.',
            'image' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?q=80&w=600&auto=format&fit=crop',
            'icon' => 'bi-graph-up-arrow',
            'badge' => 'Growth',
            'plan_type' => 'growth',
            'growth_rate' => 15,
            'lock_duration' => '3 Months', // legacy display fallback; real durations live in plan_durations
            'investment_amount' => 499.00,
            'daily_profit' => 0.16,
            'total_return' => 513.76,
            'min_goal' => 499.00,
            'is_active' => true,
            'sort_order' => 1,
            'max_purchase_per_user' => null,
            'cooldown_days' => null,
            'requires_plan_id' => null,
            'unlock_enabled' => false,
            'marketing_badge' => '⭐ Recommended',
            'risk_level' => 'Low',
            'auto_mature' => true,
            'highlights' => json_encode(['Unlocks Trust Builder Plan', 'Daily Portfolio Tracking', 'Priority Verification']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('plan_durations')->insert([
            [
                'plan_id' => $growthPlanId, 'label' => '3 Months', 'duration_days' => 90,
                'growth_rate' => 12, 'daily_profit' => 0.16, 'total_return' => 513.76,
                'is_default' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'plan_id' => $growthPlanId, 'label' => '6 Months', 'duration_days' => 180,
                'growth_rate' => 15, 'daily_profit' => 0.21, 'total_return' => 535.92,
                'is_default' => false, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'plan_id' => $growthPlanId, 'label' => '1 Year', 'duration_days' => 365,
                'growth_rate' => 18, 'daily_profit' => 0.25, 'total_return' => 588.82,
                'is_default' => false, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $trustBuilderPlanId = DB::table('plans')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'title' => 'Trust Builder Plan',
            'subtitle' => 'Your first successful investment and withdrawal experience.',
            'image' => 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?q=80&w=600&auto=format&fit=crop',
            'icon' => 'bi-shield-check',
            'badge' => 'Starter',
            'plan_type' => 'trust_builder',
            'growth_rate' => 10,
            'lock_duration' => '1 Day',
            'investment_amount' => 199.00,
            'daily_profit' => 20.00,
            'total_return' => 219.00,
            'min_goal' => 199.00,
            'is_active' => true,
            'sort_order' => 0,
            'max_purchase_per_user' => 1,
            'cooldown_days' => null,
            'requires_plan_id' => $growthPlanId,
            'unlock_enabled' => true,
            'unlock_message' => 'To unlock the Trust Builder Plan, please activate a Growth Plan first.',
            'risk_level' => 'Low',
            'auto_mature' => true,
            'highlights' => json_encode(['Fast 1-Day Return', 'Beginner Friendly', 'One-Time Welcome Offer']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('plan_durations')->insert([
            'plan_id' => $trustBuilderPlanId, 'label' => '1 Day', 'duration_days' => 1,
            'growth_rate' => 10, 'daily_profit' => 20.00, 'total_return' => 219.00,
            'is_default' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $planIds = DB::table('plans')->whereIn('plan_type', ['trust_builder', 'growth'])->pluck('id');
        DB::table('plan_durations')->whereIn('plan_id', $planIds)->delete();
        DB::table('plans')->whereIn('id', $planIds)->delete();
        DB::table('plan_categories')->whereIn('name', ['Starter', 'Growth'])->delete();
    }
};
