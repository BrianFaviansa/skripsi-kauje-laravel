<?php

use Illuminate\Support\Facades\Route;
use Modules\Job\Http\Controllers\JobController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('jobs', JobController::class)->names('job');
});
