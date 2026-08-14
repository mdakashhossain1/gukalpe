<?php

use App\Modules\Admin\Controllers\AdminController;
use App\Modules\Admin\Controllers\BackupController;
use App\Modules\Admin\Controllers\BannerController;
use App\Modules\Admin\Controllers\PaymentGatewayController;
use App\Modules\Admin\Controllers\PlanManagementController;
use App\Modules\Admin\Controllers\ReferralCommissionController;
use App\Modules\Admin\Controllers\ReportController;
use App\Modules\Admin\Controllers\RoleController;
use App\Modules\Admin\Controllers\WithdrawalSettingsController;
use Illuminate\Support\Facades\Route;

// Slug comes from config/admin.php (ADMIN_PANEL_SLUG in .env) - change the
// env value any time, no code change needed. Deliberately not "admin" in
// any of the sub-paths either.
$slug = config('admin.panel_slug', 'admin');

Route::prefix($slug)->group(function () {
    Route::get('/', [AdminController::class, 'login'])->name('admin.login');
    Route::post('/', [AdminController::class, 'authenticate'])->name('admin.authenticate');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
        Route::post('/users/{user}/toggle-ban', [AdminController::class, 'toggleBanUser'])->name('admin.users.toggle-ban');
        Route::get('/deposits', [AdminController::class, 'deposits'])->name('admin.deposits');
        Route::post('/deposits/{deposit}/approve', [AdminController::class, 'approveDeposit'])->name('admin.deposits.approve');
        Route::post('/deposits/{deposit}/reject', [AdminController::class, 'rejectDeposit'])->name('admin.deposits.reject');
        Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('admin.withdrawals');
        Route::post('/withdrawals/{withdraw}/approve', [AdminController::class, 'approveWithdrawal'])->name('admin.withdrawals.approve');
        Route::post('/withdrawals/{withdraw}/reject', [AdminController::class, 'rejectWithdrawal'])->name('admin.withdrawals.reject');
        Route::get('/transactions', [AdminController::class, 'transactions'])->name('admin.transactions');

        Route::get('/referral-commissions', [ReferralCommissionController::class, 'index'])->name('admin.referral-commissions');
        Route::post('/referral-commissions/{referralCommission}/approve', [ReferralCommissionController::class, 'approve'])->name('admin.referral-commissions.approve');
        Route::post('/referral-commissions/{referralCommission}/reject', [ReferralCommissionController::class, 'reject'])->name('admin.referral-commissions.reject');
        Route::post('/referral-commissions/{referralCommission}/adjust', [ReferralCommissionController::class, 'adjust'])->name('admin.referral-commissions.adjust');
        Route::post('/referral-commissions/{referralCommission}/reverse', [ReferralCommissionController::class, 'reverse'])->name('admin.referral-commissions.reverse');

        Route::get('/banners', [BannerController::class, 'index'])->name('admin.banners');
        Route::get('/banners/create', [BannerController::class, 'create'])->name('admin.banners.create');
        Route::post('/banners', [BannerController::class, 'store'])->name('admin.banners.store');
        Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('admin.banners.edit');
        Route::post('/banners/{banner}', [BannerController::class, 'update'])->name('admin.banners.update');
        Route::post('/banners/{banner}/toggle-active', [BannerController::class, 'toggleActive'])->name('admin.banners.toggle-active');
        Route::post('/banners/{banner}/duplicate', [BannerController::class, 'duplicate'])->name('admin.banners.duplicate');
        Route::post('/banners/{banner}/delete', [BannerController::class, 'destroy'])->name('admin.banners.delete');

        Route::get('/plan-analytics', [AdminController::class, 'planAnalytics'])->name('admin.plan-analytics');

        Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
        Route::get('/reports/print', [ReportController::class, 'printable'])->name('admin.reports.print');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');

        Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles');
        Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::post('/roles/{adminUser}/toggle-active', [RoleController::class, 'toggleActive'])->name('admin.roles.toggle-active');
        Route::post('/roles/{adminUser}/delete', [RoleController::class, 'destroy'])->name('admin.roles.delete');

        Route::get('/backups', [BackupController::class, 'index'])->name('admin.backups');
        Route::post('/backups', [BackupController::class, 'create'])->name('admin.backups.create');
        Route::get('/backups/{file}/download', [BackupController::class, 'download'])->name('admin.backups.download');
        Route::post('/backups/{file}/restore', [BackupController::class, 'restore'])->name('admin.backups.restore');
        Route::post('/backups/{file}/delete', [BackupController::class, 'destroy'])->name('admin.backups.delete');
        Route::get('/notifications/poll', [AdminController::class, 'pollNotifications'])->name('admin.notifications.poll');
        Route::post('/notifications/read', [AdminController::class, 'markNotificationsRead'])->name('admin.notifications.read');
        // Wallet adjustment has no standalone page anymore - it lives inline in
        // the Users table (per-row "Adjust" -> shared modal posts here).
        Route::post('/wallet-tools/adjust', [AdminController::class, 'adjustWallet'])->name('admin.wallet-tools.adjust');
        Route::get('/simulations', [AdminController::class, 'simulations'])->name('admin.simulations');
        Route::get('/settings', [AdminController::class, 'settingsPage'])->name('admin.settings');
        Route::post('/settings/referral-toggle', [AdminController::class, 'toggleReferral'])->name('admin.settings.referral-toggle');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
        Route::get('/withdrawal-settings', [WithdrawalSettingsController::class, 'index'])->name('admin.withdrawal-settings');
        Route::post('/withdrawal-settings', [WithdrawalSettingsController::class, 'update'])->name('admin.withdrawal-settings.update');
        Route::get('/logs', [AdminController::class, 'logs'])->name('admin.logs');
        Route::get('/push-notification', [AdminController::class, 'pushNotificationForm'])->name('admin.push-notification');
        Route::post('/push-notification', [AdminController::class, 'sendPushNotification'])->name('admin.push-notification.send');
        Route::get('/plans', [PlanManagementController::class, 'index'])->name('admin.plans');
        Route::get('/plans/create', [PlanManagementController::class, 'create'])->name('admin.plans.create');
        Route::post('/plans', [PlanManagementController::class, 'store'])->name('admin.plans.store');
        Route::get('/plans/{plan}/edit', [PlanManagementController::class, 'edit'])->name('admin.plans.edit');
        Route::post('/plans/{plan}', [PlanManagementController::class, 'update'])->name('admin.plans.update');
        Route::post('/plans/{plan}/toggle-active', [PlanManagementController::class, 'toggleActive'])->name('admin.plans.toggle-active');
        Route::post('/plans/{plan}/delete', [PlanManagementController::class, 'destroy'])->name('admin.plans.delete');

        Route::get('/payment-gateway', [PaymentGatewayController::class, 'index'])->name('admin.payment-gateway');
        Route::post('/payment-gateway/settings', [PaymentGatewayController::class, 'updateSettings'])->name('admin.payment-gateway.settings');

        Route::get('/payment-gateway/upi-accounts/create', [PaymentGatewayController::class, 'createUpi'])->name('admin.payment-gateway.upi-accounts.create');
        Route::post('/payment-gateway/upi-accounts', [PaymentGatewayController::class, 'storeUpi'])->name('admin.payment-gateway.upi-accounts.store');
        Route::get('/payment-gateway/upi-accounts/{upiAccount}/edit', [PaymentGatewayController::class, 'editUpi'])->name('admin.payment-gateway.upi-accounts.edit');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}', [PaymentGatewayController::class, 'updateUpi'])->name('admin.payment-gateway.upi-accounts.update');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}/toggle-active', [PaymentGatewayController::class, 'toggleUpiActive'])->name('admin.payment-gateway.upi-accounts.toggle-active');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}/move', [PaymentGatewayController::class, 'moveUpi'])->name('admin.payment-gateway.upi-accounts.move');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}/delete', [PaymentGatewayController::class, 'deleteUpi'])->name('admin.payment-gateway.upi-accounts.delete');

        Route::get('/payment-gateway/bank-accounts/create', [PaymentGatewayController::class, 'createBank'])->name('admin.payment-gateway.bank-accounts.create');
        Route::post('/payment-gateway/bank-accounts', [PaymentGatewayController::class, 'storeBank'])->name('admin.payment-gateway.bank-accounts.store');
        Route::get('/payment-gateway/bank-accounts/{bankAccount}/edit', [PaymentGatewayController::class, 'editBank'])->name('admin.payment-gateway.bank-accounts.edit');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}', [PaymentGatewayController::class, 'updateBank'])->name('admin.payment-gateway.bank-accounts.update');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}/toggle-active', [PaymentGatewayController::class, 'toggleBankActive'])->name('admin.payment-gateway.bank-accounts.toggle-active');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}/move', [PaymentGatewayController::class, 'moveBank'])->name('admin.payment-gateway.bank-accounts.move');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}/delete', [PaymentGatewayController::class, 'deleteBank'])->name('admin.payment-gateway.bank-accounts.delete');

        Route::get('/payment-gateway/usdt-accounts/create', [PaymentGatewayController::class, 'createUsdt'])->name('admin.payment-gateway.usdt-accounts.create');
        Route::post('/payment-gateway/usdt-accounts', [PaymentGatewayController::class, 'storeUsdt'])->name('admin.payment-gateway.usdt-accounts.store');
        Route::get('/payment-gateway/usdt-accounts/{usdtAccount}/edit', [PaymentGatewayController::class, 'editUsdt'])->name('admin.payment-gateway.usdt-accounts.edit');
        Route::post('/payment-gateway/usdt-accounts/{usdtAccount}', [PaymentGatewayController::class, 'updateUsdt'])->name('admin.payment-gateway.usdt-accounts.update');
        Route::post('/payment-gateway/usdt-accounts/{usdtAccount}/toggle-active', [PaymentGatewayController::class, 'toggleUsdtActive'])->name('admin.payment-gateway.usdt-accounts.toggle-active');
        Route::post('/payment-gateway/usdt-accounts/{usdtAccount}/move', [PaymentGatewayController::class, 'moveUsdt'])->name('admin.payment-gateway.usdt-accounts.move');
        Route::post('/payment-gateway/usdt-accounts/{usdtAccount}/delete', [PaymentGatewayController::class, 'deleteUsdt'])->name('admin.payment-gateway.usdt-accounts.delete');

        Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
    });
});
