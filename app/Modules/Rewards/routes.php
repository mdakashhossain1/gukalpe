<?php

use App\Modules\Rewards\Controllers\RewardsController;
use Illuminate\Support\Facades\Route;

Route::get('/rewards', [RewardsController::class, 'index'])->name('rewards');
