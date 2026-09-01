<?php

use App\Http\Controllers\Admin\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('services')->name('services.')->group(function () {
    Route::get('/{service}', [ServiceController::class, 'show'])
        ->name('show')
        ->middleware(['permission:services:view', 'throttle:30,1']);
});