<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TripController;

Route::prefix('trips')->name('trips.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [TripController::class, 'index'])->name('index')->middleware('permission:trips:view');
        Route::get('/{uuid}', [TripController::class, 'show'])->name('show')->middleware('permission:trips:view');
    });
});