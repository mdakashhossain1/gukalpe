<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Support\AdminRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Standalone Withdrawal Settings page (client item 6: "New / Move to
 * Separate Section... Do not keep these mixed inside Referral Program").
 * Previously these keys lived on the general admin.settings form alongside
 * commission/referral/kill-switches - this controller owns the same
 * app_settings keys, just under their own route/page. Same pattern as
 * PaymentGatewayController/BannerController being split out of
 * AdminController for one concern each.
 */
class WithdrawalSettingsController extends Controller
{
    private const KEYS = [
        'withdrawal_min_amount', 'withdrawal_daily_limit', 'withdrawal_max_per_day', 'withdrawal_max_per_transaction',
        'withdrawal_method_bank_enabled', 'withdrawal_method_upi_enabled', 'withdrawal_method_usdt_enabled',
    ];

    public function index(): View
    {
        abort_unless(AdminRoles::currentCan('manage_settings'), 403);

        return view('Admin::withdrawal-settings', [
            'settings' => AppSetting::many(AppSetting::DEFAULTS),
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('manage_settings'), 403);

        $validated = $request->validate([
            'withdrawal_min_amount' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_daily_limit' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_max_per_day' => ['nullable', 'integer', 'min:1'],
            'withdrawal_max_per_transaction' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach (['withdrawal_min_amount', 'withdrawal_daily_limit', 'withdrawal_max_per_day', 'withdrawal_max_per_transaction'] as $key) {
            if (isset($validated[$key])) {
                AppSetting::set($key, (string) $validated[$key]);
            }
        }

        foreach (['withdrawal_method_bank_enabled', 'withdrawal_method_upi_enabled', 'withdrawal_method_usdt_enabled'] as $switch) {
            AppSetting::set($switch, $request->boolean($switch) ? 'true' : 'false');
        }

        $saved = AppSetting::many(array_intersect_key(AppSetting::DEFAULTS, array_flip(self::KEYS)));

        Log::channel('admin_security')->info('Withdrawal settings updated', [
            'ip' => $request->ip(),
            'settings' => $saved,
        ]);

        AdminAuditLog::record($request, 'withdrawal_settings_updated', null, null, $saved);

        return redirect()->route('admin.withdrawal-settings')->with('success', 'Withdrawal settings saved.');
    }
}
