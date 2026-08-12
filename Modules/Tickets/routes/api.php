<?php

use Illuminate\Support\Facades\Route;
use Modules\Tickets\Http\Controllers\TicketsController;

foreach (['customer', 'clinic'] as $prefix) {
    Route::prefix($prefix)->middleware(['auth:sanctum', "panel.access:{$prefix}"])->group(function () use ($prefix) {
        Route::prefix('tickets')->group(function () use ($prefix) {
            Route::get('/', [TicketsController::class, 'index'])
                ->defaults('entry_point', $prefix);
            Route::post('/', [TicketsController::class, 'store'])
                ->defaults('entry_point', $prefix);
            Route::get('/{ticket}', [TicketsController::class, 'show'])
                ->defaults('entry_point', $prefix);
            Route::post('/{ticket}/messages', [TicketsController::class, 'storeMessage'])
                ->defaults('entry_point', $prefix);
        });
    });
}

require __DIR__ . '/api-admin.php';
