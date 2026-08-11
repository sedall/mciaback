<?php

use Illuminate\Support\Facades\Route;
use Modules\Clinics\Http\Controllers\ClinicsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('clinics', ClinicsController::class)->names('clinics');
});
