<?php

use Illuminate\Support\Facades\Route;
use Modules\Forum\Http\Controllers\ForumController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('forums', ForumController::class)->names('forum');
});
