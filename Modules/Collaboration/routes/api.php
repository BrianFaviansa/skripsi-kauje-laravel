<?php

use Illuminate\Support\Facades\Route;
use Modules\Collaboration\Http\Controllers\CollaborationController;

Route::prefix('collaborations')->group(function () {
    // Public routes
    Route::get('/', [CollaborationController::class, 'index']);
    Route::get('/{id}', [CollaborationController::class, 'show']);

    // Protected routes (Owner or Admin can update/delete)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CollaborationController::class, 'store']);
        Route::put('/{id}', [CollaborationController::class, 'update']);
        Route::delete('/{id}', [CollaborationController::class, 'destroy']);
        Route::post('/upload-image', [CollaborationController::class, 'uploadImage']);
    });
});
