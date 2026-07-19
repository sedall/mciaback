<?php

use Illuminate\Support\Facades\Route;
use Modules\Loans\Http\Controllers\LoanController;

Route::middleware(['auth:sanctum', 'panel.access:customer'])
    ->prefix('customer')
    ->group(function () {
        Route::get('loans', [LoanController::class, 'index']);
        Route::post('loans', [LoanController::class, 'store']);
    });
