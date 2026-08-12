<?php

use Illuminate\Support\Facades\Route;
use Modules\Tickets\Http\Controllers\AdminTicketsController;

Route::prefix('admin')->middleware(['auth:sanctum', 'panel.access:admin'])->group(function () {
    Route::prefix('tickets')->group(function () {
        Route::get('/', [AdminTicketsController::class, 'index']);
        Route::get('/{ticket}', [AdminTicketsController::class, 'show']);
        Route::post('/{ticket}/messages', [AdminTicketsController::class, 'storeMessage']);
        Route::patch('/{ticket}/status', [AdminTicketsController::class, 'updateStatus']);
    });
});
