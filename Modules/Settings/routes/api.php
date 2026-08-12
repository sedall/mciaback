<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\AdminSettingsController;

Route::middleware(['auth:sanctum', 'permission:panel.admin'])->prefix('admin/settings')->group(function () {

        Route::get('/', [AdminSettingsController::class, 'index']);
        Route::put('/bulk', [AdminSettingsController::class, 'updateBulk']);

    });

