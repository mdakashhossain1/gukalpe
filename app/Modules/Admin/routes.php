<?php

use App\Modules\Admin\Controllers\AdminController;
use App\Modules\Admin\Controllers\PaymentGatewayController;
use App\Modules\Admin\Controllers\PlanManagementController;
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
        Route::get('/deposits', [AdminController::class, 'deposits'])->name('admin.deposits');
        Route::post('/deposits/{deposit}/approve', [AdminController::class, 'approveDeposit'])->name('admin.deposits.approve');
        Route::post('/deposits/{deposit}/reject', [AdminController::class, 'rejectDeposit'])->name('admin.deposits.reject');
        Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('admin.withdrawals');
        Route::post('/withdrawals/{withdraw}/approve', [AdminController::class, 'approveWithdrawal'])->name('admin.withdrawals.approve');
        Route::post('/withdrawals/{withdraw}/reject', [AdminController::class, 'rejectWithdrawal'])->name('admin.withdrawals.reject');
        Route::get('/notifications/poll', [AdminController::class, 'pollNotifications'])->name('admin.notifications.poll');
        Route::post('/notifications/read', [AdminController::class, 'markNotificationsRead'])->name('admin.notifications.read');
        Route::get('/wallet-tools', [AdminController::class, 'walletTools'])->name('admin.wallet-tools');
        Route::get('/simulations', [AdminController::class, 'simulations'])->name('admin.simulations');
        Route::get('/settings', [AdminController::class, 'settingsPage'])->name('admin.settings');
        Route::post('/settings/referral-toggle', [AdminController::class, 'toggleReferral'])->name('admin.settings.referral-toggle');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
        Route::get('/logs', [AdminController::class, 'logs'])->name('admin.logs');
        Route::get('/push-notification', [AdminController::class, 'pushNotificationForm'])->name('admin.push-notification');
        Route::post('/push-notification', [AdminController::class, 'sendPushNotification'])->name('admin.push-notification.send');
        Route::get('/plans', [PlanManagementController::class, 'index'])->name('admin.plans');
        Route::get('/plans/create', [PlanManagementController::class, 'create'])->name('admin.plans.create');
        Route::post('/plans', [PlanManagementController::class, 'store'])->name('admin.plans.store');
        Route::get('/plans/{plan}/edit', [PlanManagementController::class, 'edit'])->name('admin.plans.edit');
        Route::post('/plans/{plan}', [PlanManagementController::class, 'update'])->name('admin.plans.update');
        Route::post('/plans/{plan}/toggle-active', [PlanManagementController::class, 'toggleActive'])->name('admin.plans.toggle-active');

        Route::get('/payment-gateway', [PaymentGatewayController::class, 'index'])->name('admin.payment-gateway');
        Route::post('/payment-gateway/settings', [PaymentGatewayController::class, 'updateSettings'])->name('admin.payment-gateway.settings');

        Route::get('/payment-gateway/upi-accounts/create', [PaymentGatewayController::class, 'createUpi'])->name('admin.payment-gateway.upi-accounts.create');
        Route::post('/payment-gateway/upi-accounts', [PaymentGatewayController::class, 'storeUpi'])->name('admin.payment-gateway.upi-accounts.store');
        Route::get('/payment-gateway/upi-accounts/{upiAccount}/edit', [PaymentGatewayController::class, 'editUpi'])->name('admin.payment-gateway.upi-accounts.edit');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}', [PaymentGatewayController::class, 'updateUpi'])->name('admin.payment-gateway.upi-accounts.update');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}/toggle-active', [PaymentGatewayController::class, 'toggleUpiActive'])->name('admin.payment-gateway.upi-accounts.toggle-active');
        Route::post('/payment-gateway/upi-accounts/{upiAccount}/delete', [PaymentGatewayController::class, 'deleteUpi'])->name('admin.payment-gateway.upi-accounts.delete');

        Route::get('/payment-gateway/bank-accounts/create', [PaymentGatewayController::class, 'createBank'])->name('admin.payment-gateway.bank-accounts.create');
        Route::post('/payment-gateway/bank-accounts', [PaymentGatewayController::class, 'storeBank'])->name('admin.payment-gateway.bank-accounts.store');
        Route::get('/payment-gateway/bank-accounts/{bankAccount}/edit', [PaymentGatewayController::class, 'editBank'])->name('admin.payment-gateway.bank-accounts.edit');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}', [PaymentGatewayController::class, 'updateBank'])->name('admin.payment-gateway.bank-accounts.update');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}/toggle-active', [PaymentGatewayController::class, 'toggleBankActive'])->name('admin.payment-gateway.bank-accounts.toggle-active');
        Route::post('/payment-gateway/bank-accounts/{bankAccount}/delete', [PaymentGatewayController::class, 'deleteBank'])->name('admin.payment-gateway.bank-accounts.delete');

        Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
    });
});
