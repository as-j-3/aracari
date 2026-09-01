<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VehicleController;

Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index')->middleware('permission:vehicles:view');
    });
});