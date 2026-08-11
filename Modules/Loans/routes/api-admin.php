<?php

use Illuminate\Support\Facades\Route;
use Modules\Loans\Http\Controllers\AdminLoansController;


Route::prefix('admin')->middleware(['auth:sanctum', 'panel.access:admin'])->group(function () {
    Route::get('loans', [AdminLoansController::class, 'index']);
    Route::get('loans/{loan}', [AdminLoansController::class, 'show']);
    Route::patch('loans/{loan}/approve', [AdminLoansController::class, 'approve']);
    Route::patch('loans/{loan}/fund', [AdminLoansController::class, 'fund']);
    Route::patch('loans/{loan}/reject', [AdminLoansController::class, 'reject']);
    Route::get('loans/{loan}/installments', [AdminLoansController::class, 'installments']);
    Route::post('/loans/{loan}/installments/{installment}/repay', [AdminLoansController::class, 'repay']);
});
