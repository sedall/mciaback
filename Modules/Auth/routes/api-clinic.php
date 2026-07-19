<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\MeController;

Route::prefix('clinic')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtp'])->name('auth.request-clinic-otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-clinic-otp');

    Route::middleware(['auth:sanctum', 'panel.access:clinic'])->group(function () {
        Route::get('/me', [MeController::class, 'show'])->name('auth.clinic.me');
    });
});
