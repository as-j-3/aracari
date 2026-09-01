<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VehicleTypeController;

Route::prefix('vehicle-types')->name('vehicle_types.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [VehicleTypeController::class, 'index'])->name('index')->middleware('permission:vehicle_types:view');
        Route::get('/create', [VehicleTypeController::class, 'create'])->name('create')->middleware('permission:vehicle_types:create');
        Route::get('/{vehicleType:uuid}/edit', [VehicleTypeController::class, 'edit'])->name('edit')->middleware('permission:vehicle_types:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [VehicleTypeController::class, 'store'])->name('store')->middleware('permission:vehicle_types:create');
        Route::put('/{vehicleType:uuid}', [VehicleTypeController::class, 'update'])->name('update')->middleware('permission:vehicle_types:edit');
        Route::delete('/{vehicleType:uuid}', [VehicleTypeController::class, 'destroy'])->name('destroy')->middleware('permission:vehicle_types:delete');
    });
});