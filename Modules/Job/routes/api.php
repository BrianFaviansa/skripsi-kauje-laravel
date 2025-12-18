<?php

use Illuminate\Support\Facades\Route;
use Modules\Job\Http\Controllers\JobController;

Route::prefix('jobs')->group(function () {
    // Public routes
    Route::get('/', [JobController::class, 'index']);
    Route::get('/{id}', [JobController::class, 'show']);

    // Protected routes (Owner or Admin can update/delete)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [JobController::class, 'store']);
        Route::put('/{id}', [JobController::class, 'update']);
        Route::delete('/{id}', [JobController::class, 'destroy']);
        Route::post('/upload-image', [JobController::class, 'uploadImage']);
    });
});
