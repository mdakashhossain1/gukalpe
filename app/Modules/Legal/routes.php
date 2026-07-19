<?php

use App\Modules\Legal\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/faq', [LegalController::class, 'faq'])->name('legal.faq');
