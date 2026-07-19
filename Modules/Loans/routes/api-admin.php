<?php

use Illuminate\Support\Facades\Route;
use Modules\Loans\Http\Controllers\AdminLoansController;


Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('loans', [AdminLoansController::class, 'index']);
    Route::get('loans/{loan}', [AdminLoansController::class, 'show']);
    Route::patch('loans/{loan}/approve', [AdminLoansController::class, 'approve']);
    Route::patch('loans/{loan}/fund', [AdminLoansController::class, 'fund']);
    Route::patch('loans/{loan}/reject', [AdminLoansController::class, 'reject']);
});
