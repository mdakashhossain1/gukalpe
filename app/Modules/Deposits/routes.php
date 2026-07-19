<?php

use App\Modules\Deposits\Controllers\DepositRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/add-money', [DepositRequestController::class, 'create'])->name('deposits.create');
Route::post('/add-money', [DepositRequestController::class, 'store'])->name('deposits.store');

// Home's amount input POSTs here first so the typed amount never appears in
// a URL (query string, browser history, referrer, server access logs) - it
// gets flashed to the session and picked up once by create() after the
// redirect, then Laravel clears it automatically.
Route::post('/add-money/start', [DepositRequestController::class, 'start'])->name('deposits.start');
