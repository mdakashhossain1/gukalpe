<?php

use App\Modules\Auth\Controllers\GoogleAuthController;
use App\Modules\Auth\Controllers\PhoneAuthController;
use Illuminate\Support\Facades\Route;

// Google login: real, server-side (Socialite + a real `users` table) since
// OAuth fundamentally requires a server redirect round-trip;
// resources/js/modules/auth.js bridges the result into the client-side
// state the rest of the app still reads (see DESIGN.md).
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/auth/google/link-phone', [GoogleAuthController::class, 'linkPhone'])->name('auth.google.link-phone');

// Phone + OTP + MPIN login: real, server-side (App\Models\User,
// App\Models\PhoneOtp, real Laravel sessions) - replaced the old
// client-side simulation (hardcoded '123456' OTP, MPIN in localStorage)
// that used to live entirely in the auth-overlay modal. Same bridge
// mechanism as Google above: resources/js/modules/auth.js picks up the
// flashed result and folds it into the same client-side state the rest of
// the (not-yet-migrated) app reads. See DESIGN.md's "Real phone
// authentication" section.
Route::get('/login', [PhoneAuthController::class, 'showPhoneForm'])->name('login');
Route::post('/login', [PhoneAuthController::class, 'submitPhone'])->name('login.submit');
Route::get('/login/verify-otp', [PhoneAuthController::class, 'showOtpForm'])->name('login.verify-otp');
Route::post('/login/verify-otp', [PhoneAuthController::class, 'verifyOtp'])->name('login.verify-otp.submit');
Route::post('/login/resend-otp', [PhoneAuthController::class, 'resendOtp'])->name('login.resend-otp');
Route::get('/login/set-mpin', [PhoneAuthController::class, 'showSetMpinForm'])->name('login.set-mpin');
Route::post('/login/set-mpin', [PhoneAuthController::class, 'setMpin'])->name('login.set-mpin.submit');
Route::get('/login/mpin', [PhoneAuthController::class, 'showMpinForm'])->name('login.mpin');
Route::post('/login/mpin', [PhoneAuthController::class, 'verifyMpin'])->name('login.mpin.submit');
Route::get('/login/forgot-mpin', [PhoneAuthController::class, 'showForgotMpinForm'])->name('login.forgot-mpin');
Route::post('/login/forgot-mpin', [PhoneAuthController::class, 'submitForgotMpin'])->name('login.forgot-mpin.submit');

Route::post('/logout', [PhoneAuthController::class, 'logout'])->name('logout');
