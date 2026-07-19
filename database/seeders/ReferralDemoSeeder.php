<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReferralDemoSeeder extends Seeder
{
    // Two clearly-named, reproducible demo accounts (idempotent via
    // updateOrCreate/firstOrCreate, safe to re-run) showing the Refer & Earn
    // feature already mid-flight: a referrer whose friend joined and
    // invested, so /rewards has real data to look at without manually
    // signing up test accounts through the UI every time.
    public function run(): void
    {
        // Real column requires a value; phone/MPIN users never use it to
        // log in, same convention PhoneAuthController uses for real signups.
        $randomPassword = fn () => Hash::make(Str::random(40));

        $referrer = User::updateOrCreate(
            ['phone' => '9000000001'],
            [
                'name' => 'Demo Referrer',
                'email' => '9000000001@phone.gullakpe.local',
                'password' => $randomPassword(),
                'mpin' => '1234',
                'referral_code' => 'GULDEMO1',
            ]
        );

        $friend = User::updateOrCreate(
            ['phone' => '9000000002'],
            [
                'name' => 'Demo Friend',
                'email' => '9000000002@phone.gullakpe.local',
                'password' => $randomPassword(),
                'mpin' => '1234',
                'referred_by' => $referrer->id,
                'created_at' => now()->subDays(5),
            ]
        );

        $plan = Plan::active()->ordered()->first();
        if (! $plan) {
            return;
        }

        $userPlan = UserPlan::firstOrCreate(
            ['user_id' => $friend->id, 'plan_id' => $plan->id],
            [
                'invested_amount' => $plan->investment_amount,
                'daily_profit_val' => $plan->daily_profit,
                'status' => UserPlan::STATUS_ACTIVE,
                'purchased_at' => now()->subDays(2),
            ]
        );

        $percent = (float) AppSetting::get('commission_percent', '5');
        $amount = round((float) $plan->investment_amount * $percent / 100, 2);

        $commission = ReferralCommission::firstOrCreate(
            ['user_plan_id' => $userPlan->id],
            [
                'referrer_id' => $referrer->id,
                'referred_user_id' => $friend->id,
                'amount' => $amount,
                'commission_percent' => $percent,
            ]
        );

        if ($commission->wasRecentlyCreated) {
            WalletBalance::credit($referrer->phone, $amount);
        }
    }
}
