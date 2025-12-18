<?php

use Illuminate\Support\Facades\Route;
use Modules\Forum\Http\Controllers\ForumController;

Route::prefix('forums')->group(function () {
    // Public routes
    Route::get('/', [ForumController::class, 'index']);
    Route::get('/{id}', [ForumController::class, 'show']);
    Route::get('/{forumId}/comments', [ForumController::class, 'getComments']);
    Route::get('/{forumId}/likes', [ForumController::class, 'getLikes']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Forum CRUD
        Route::post('/', [ForumController::class, 'store']);
        Route::put('/{id}', [ForumController::class, 'update']);
        Route::delete('/{id}', [ForumController::class, 'destroy']);
        Route::post('/upload-image', [ForumController::class, 'uploadImage']);

        // Comments
        Route::post('/{forumId}/comments', [ForumController::class, 'storeComment']);
        Route::put('/comments/{commentId}', [ForumController::class, 'updateComment']);
        Route::delete('/comments/{commentId}', [ForumController::class, 'destroyComment']);

        // Likes
        Route::post('/{forumId}/like', [ForumController::class, 'toggleLike']);
    });
});
