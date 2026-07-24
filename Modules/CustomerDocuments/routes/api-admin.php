<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomerDocuments\Http\Controllers\AdminCustomerDocumentsController;

Route::middleware(['auth:sanctum', 'panel.access:admin'])->prefix('admin')->group(function () {
    Route::get('documents', [AdminCustomerDocumentsController::class, 'index',]);
    Route::get('documents/{customerDocument}', [AdminCustomerDocumentsController::class, 'show',]);
    Route::patch('documents/{customerDocument}/approve', [AdminCustomerDocumentsController::class, 'approve',]);
    Route::patch('documents/{customerDocument}/reject', [AdminCustomerDocumentsController::class, 'reject',]);
});
