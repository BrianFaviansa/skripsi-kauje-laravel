<?php

use Illuminate\Support\Facades\Route;
use Modules\Job\Http\Controllers\JobController;

Route::prefix('jobs')->group(function () {
    Route::get('/', [JobController::class, 'index']);
    Route::get('/{id}', [JobController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [JobController::class, 'store']);
        Route::put('/{id}', [JobController::class, 'update']);
        Route::delete('/{id}', [JobController::class, 'destroy']);
        Route::post('/upload-image', [JobController::class, 'uploadImage']);
    });
});
