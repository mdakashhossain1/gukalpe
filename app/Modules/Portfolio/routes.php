<?php

use App\Modules\Portfolio\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio');
