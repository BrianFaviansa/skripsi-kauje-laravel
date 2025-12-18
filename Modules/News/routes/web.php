<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\NewsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('news', NewsController::class)->names('news');
});
