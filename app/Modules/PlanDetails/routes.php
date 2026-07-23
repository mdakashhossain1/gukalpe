<?php

use App\Modules\PlanDetails\Controllers\PlanDetailsController;
use Illuminate\Support\Facades\Route;

Route::get('/plan-details/{plan}', [PlanDetailsController::class, 'index'])->name('plan-details');
Route::post('/plan-details/{plan}/favorite', [PlanDetailsController::class, 'toggleFavorite'])->name('plan-details.favorite');
