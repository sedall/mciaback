<?php


use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

foreach (['customer', 'clinic', 'admin'] as $prefix) {
    Route::prefix($prefix)->group(function () use ($prefix) {
        Route::prefix('auth')->group(function () use ($prefix) {
            Route::post('/request-otp', [AuthController::class, 'requestOtp'])
                ->defaults('entry_point', $prefix);

            Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
                ->defaults('entry_point', $prefix);
        });
    });
}
require __DIR__ . '/api-customer.php';
require __DIR__ . '/api-clinic.php';
require __DIR__ . '/api-admin.php';
