<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\MeController;

Route::prefix('customer')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    Route::middleware(['auth:sanctum', 'panel.access:customer'])->group(function () {
        Route::get('/me', [MeController::class, 'show']);
    });
});

