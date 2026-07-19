<?php

use Illuminate\Support\Facades\Route;
use Modules\Loans\Http\Controllers\AdminLoanController;

Route::middleware(['auth:sanctum', 'panel.access:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('loans', [AdminLoanController::class, 'index']);
        Route::get('loans/{loan}', [AdminLoanController::class, 'show']);
        Route::patch('loans/{loan}/approve', [AdminLoanController::class, 'approve']);
        Route::patch('loans/{loan}/fund', [AdminLoanController::class, 'fund']);
    });
