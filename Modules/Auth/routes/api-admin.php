<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\MeController;

Route::prefix('admin')->group(function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtp'])->name('auth.request-admin-otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-admin-otp');

    Route::middleware(['auth:sanctum', 'panel.access:admin'])->group(function () {
        Route::get('/me', [MeController::class, 'show'])->name('auth.admin.me');
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});
