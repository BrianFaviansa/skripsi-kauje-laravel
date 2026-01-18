<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\NewsController;

Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('/{id}', [NewsController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [NewsController::class, 'store']);
        Route::put('/{id}', [NewsController::class, 'update']);
        Route::delete('/{id}', [NewsController::class, 'destroy']);
        Route::post('/upload-image', [NewsController::class, 'uploadImage']);
    });
});
