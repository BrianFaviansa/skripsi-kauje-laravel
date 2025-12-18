<?php

use Illuminate\Support\Facades\Route;
use Modules\Collaboration\Http\Controllers\CollaborationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('collaborations', CollaborationController::class)->names('collaboration');
});
