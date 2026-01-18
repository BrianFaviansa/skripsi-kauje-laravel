<?php

use Illuminate\Support\Facades\Route;
use Modules\Forum\Http\Controllers\ForumController;

Route::prefix('forums')->group(function () {
    Route::get('/', [ForumController::class, 'index']);
    Route::get('/{id}', [ForumController::class, 'show']);
    Route::get('/{forumId}/comments', [ForumController::class, 'getComments']);
    Route::get('/{forumId}/likes', [ForumController::class, 'getLikes']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ForumController::class, 'store']);
        Route::put('/{id}', [ForumController::class, 'update']);
        Route::delete('/{id}', [ForumController::class, 'destroy']);
        Route::post('/upload-image', [ForumController::class, 'uploadImage']);

        Route::post('/{forumId}/comments', [ForumController::class, 'storeComment']);
        Route::put('/comments/{commentId}', [ForumController::class, 'updateComment']);
        Route::delete('/comments/{commentId}', [ForumController::class, 'destroyComment']);

        Route::post('/{forumId}/like', [ForumController::class, 'toggleLike']);
    });
});
