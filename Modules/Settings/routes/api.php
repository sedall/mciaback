<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\AdminSettingsController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Admin settings routes
    Route::prefix('admin/settings')->group(function () {
        Route::get('/', [AdminSettingsController::class, 'index']);
        Route::put('/bulk', [AdminSettingsController::class, 'updateBulk']);
    });
});
