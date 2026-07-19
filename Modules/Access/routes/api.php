<?php

use Illuminate\Support\Facades\Route;
use Modules\Access\Http\Controllers\AccessController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('accesses', AccessController::class)->names('access');
});
