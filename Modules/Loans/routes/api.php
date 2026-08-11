<?php

use Illuminate\Support\Facades\Route;
use Modules\Loans\Http\Controllers\LoansController;

Route::middleware(['auth:sanctum', 'panel.access:customer'])
    ->prefix('customer')
    ->group(function () {
        Route::get('loans', [LoansController::class, 'index']);
        Route::post('loans', [LoansController::class, 'store']);
        Route::get('loans/{loan}/installments', [LoansController::class, 'installments']);
        Route::post('loans/{loan}/installments/{installment}/repay', [LoansController::class, 'repay']);
    });

