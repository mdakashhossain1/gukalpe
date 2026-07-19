<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shares real, admin-controlled app config with the main SPA shell
        // layout so resources/js/modules/*.js can read it off `window`
        // instead of the localStorage flags the Ops Console used to write
        // (which never left the admin's own browser - see AppSetting).
        View::composer('layouts.app', function ($view) {
            $settings = AppSetting::current();

            $view->with('appSettings', [
                'referralEnabled' => $settings['referral_enabled'] === 'true',
                'commissionPercent' => (float) $settings['commission_percent'],
                'cashbackAmount' => (float) $settings['cashback_amount'],
                'settlementTime' => $settings['settlement_time'],
                'maxDepositLimit' => (float) $settings['max_deposit_limit'],
            ]);
        });
    }
}
