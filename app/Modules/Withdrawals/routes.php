<?php

use App\Modules\Withdrawals\Controllers\WithdrawRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/withdraw-money', [WithdrawRequestController::class, 'create'])->name('withdrawals.create');
Route::post('/withdraw-money', [WithdrawRequestController::class, 'store'])->name('withdrawals.store');
