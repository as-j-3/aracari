<?php

use App\Http\Controllers\Provider\VehicleController;
use Illuminate\Support\Facades\Route;



Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index')
            ->middleware('permission:provider:vehicles:view');
        Route::get('/create', [VehicleController::class, 'create'])->name('create')
            ->middleware('permission:provider:vehicles:create');
        Route::get('/{vehicle:uuid}', [VehicleController::class, 'edit'])->name('edit')
            ->middleware('permission:provider:vehicles:edit');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [VehicleController::class, 'store'])->name('store')
            ->middleware('permission:provider:vehicles:create');
        Route::put('/{vehicle:uuid}', [VehicleController::class, 'update'])->name('update')
            ->middleware('permission:provider:vehicles:edit');
        Route::delete('/{vehicle:uuid}', [VehicleController::class, 'destroy'])->name('destroy')
            ->middleware('permission:provider:vehicles:delete');
    });
});
