<?php

use App\Modules\Explore\Controllers\ExploreController;
use Illuminate\Support\Facades\Route;

Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
Route::get('/explore/compare', [ExploreController::class, 'compare'])->name('explore.compare');
