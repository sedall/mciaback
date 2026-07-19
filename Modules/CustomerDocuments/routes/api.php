<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomerDocuments\Http\Controllers\CustomerDocumentsController;

Route::middleware(['auth:sanctum', 'panel.access:customer'])->prefix('customer')->group(function () {
    Route::get('documents', [CustomerDocumentsController::class, 'index']);
    Route::post('documents', [CustomerDocumentsController::class, 'store']);
});
