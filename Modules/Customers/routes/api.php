<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\CustomerProfileController;

Route::middleware(['auth:sanctum', 'panel.access:customer'])
    ->prefix('customer')
    ->group(function () {
        Route::get('/profile', [CustomerProfileController::class, 'show']);
        Route::post('/profile', [CustomerProfileController::class, 'upsert']);
    });
