<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('users', UserController::class)->names('user');
});
