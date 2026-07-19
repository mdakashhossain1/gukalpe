<?php

use App\Modules\Plans\Controllers\PlanPurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('/plans/{plan}/purchase', [PlanPurchaseController::class, 'purchase'])->name('plans.purchase');
