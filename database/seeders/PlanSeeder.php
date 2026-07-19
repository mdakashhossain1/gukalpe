<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    // Exact values migrated from the old hardcoded `plansData` object in
    // resources/js/modules/animations.js, so the catalog is unchanged the
    // moment this replaces it - only the source (DB vs. hardcoded JS) changes.
    public function run(): void
    {
        $plans = [
            ['title' => 'Emergency Fund', 'subtitle' => 'Build a secure safety net for peace of mind', 'image' => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?q=80&w=600&auto=format&fit=crop', 'icon' => 'bi-piggy-bank', 'badge' => 'Beginner', 'growth_rate' => 8, 'lock_duration' => 'Flexible', 'investment_amount' => 199, 'daily_profit' => 15.50, 'total_return' => 300, 'min_goal' => 300, 'sort_order' => 1],
            ['title' => 'Dream Superbike', 'subtitle' => 'Own your dream ride faster', 'image' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=600&auto=format&fit=crop', 'icon' => 'bi-speedometer2', 'badge' => 'Trending', 'growth_rate' => 12, 'lock_duration' => '12 Months', 'investment_amount' => 399, 'daily_profit' => 123.30, 'total_return' => 800, 'min_goal' => 800, 'sort_order' => 2],
            ['title' => 'Dream Home Fund', 'subtitle' => 'Build your own space step-by-step', 'image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=600&auto=format&fit=crop', 'icon' => 'bi-house-heart', 'badge' => 'Fast Return', 'growth_rate' => 16, 'lock_duration' => '36 Months', 'investment_amount' => 799, 'daily_profit' => 657.50, 'total_return' => 2000, 'min_goal' => 2000, 'sort_order' => 3],
            ['title' => 'iPhone 16 Pro Goal', 'subtitle' => 'Get the latest iPhone with smart savings', 'image' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?q=80&w=600&auto=format&fit=crop', 'icon' => 'bi-phone', 'badge' => 'Trending', 'growth_rate' => 12, 'lock_duration' => '12 Months', 'investment_amount' => 999, 'daily_profit' => 98.50, 'total_return' => 150000, 'min_goal' => 50000, 'sort_order' => 4],
            ['title' => 'Dubai Luxury Trip', 'subtitle' => 'Save for your dream vacation in style', 'image' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?q=80&w=600&auto=format&fit=crop', 'icon' => 'bi-airplane-fill', 'badge' => 'Verified', 'growth_rate' => 15, 'lock_duration' => '12 Months', 'investment_amount' => 1499, 'daily_profit' => 245.50, 'total_return' => 4000, 'min_goal' => 4000, 'sort_order' => 5],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['title' => $plan['title']], $plan);
        }
    }
}
