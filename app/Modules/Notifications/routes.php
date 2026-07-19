<?php

use App\Modules\Notifications\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
