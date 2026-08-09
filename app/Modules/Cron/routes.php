<?php

use App\Modules\Cron\Controllers\CronController;
use Illuminate\Support\Facades\Route;

Route::get('/cron/run', [CronController::class, 'run'])->name('cron.run');
