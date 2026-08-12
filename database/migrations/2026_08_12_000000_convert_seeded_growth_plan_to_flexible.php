<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 2026-08-12: a real per-term Duration choice (3/6/12 months, each its
     * own rate) became a Flexible-only feature - a Fixed plan now always
     * collapses to exactly one Duration row
     * (PlanManagementController::syncDurations()). The seeded Growth Plan
     * (2026_07_19_010300_seed_trust_builder_and_growth_plans.php) predates
     * that rule: it's Fixed-amount (₹499, no min/max) but carries 3 real
     * plan_durations rows - exactly the shape the new rule forbids. Left
     * as-is, the next admin who opened it in the edit form and saved would
     * have silently lost the 6-month/1-year options (and orphaned any real
     * holding's plan_duration_id via the nullOnDelete FK). Converts it to
     * Flexible instead - keeping all 3 real duration rows, since offering a
     * per-term choice is what Flexible is for, rather than trimming the
     * flagship plan down to one term.
     *
     * This can't just be added to the original 010300 seed migration - that
     * one runs before 2026_07_19_030000_add_investment_range_to_plans_table
     * even creates the min/max columns, so writing them there would break a
     * fresh install.
     */
    public function up(): void
    {
        $plan = DB::table('plans')->where('title', 'Growth Plan')->where('plan_type', 'growth')->first();
        if (! $plan) {
            return; // Nothing to convert - e.g. a fresh install already seeded differently.
        }

        DB::table('plans')->where('id', $plan->id)->update([
            'min_investment_amount' => 499.00,
            'max_investment_amount' => 50000.00,
            // Pinned to min - same convention PlanManagementController::validated()
            // uses for every Flexible plan saved through the admin form.
            'investment_amount' => 499.00,
            // Matches the default (3 Months) duration row - the original 15
            // never matched any real row (3mo=12, 6mo=15, 1yr=18) and was
            // just an illustrative top-level figure per the seed's own docblock.
            'growth_rate' => 12,
            'term_days' => 90,
            'daily_profit' => 0.16,
            'total_return' => 513.76,
            'lock_duration' => '3-12 Months',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $plan = DB::table('plans')->where('title', 'Growth Plan')->where('plan_type', 'growth')->first();
        if (! $plan) {
            return;
        }

        DB::table('plans')->where('id', $plan->id)->update([
            'min_investment_amount' => null,
            'max_investment_amount' => null,
            'investment_amount' => 499.00,
            'growth_rate' => 15,
            'term_days' => null,
            'daily_profit' => 0.16,
            'total_return' => 513.76,
            'lock_duration' => '3 Months',
            'updated_at' => now(),
        ]);
    }
};
