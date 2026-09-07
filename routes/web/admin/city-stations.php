<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CityStationController;

Route::prefix('city-stations')->name('city_stations.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [CityStationController::class, 'index'])->name('index')->middleware('permission:city_stations:view');
        Route::get('/create', [CityStationController::class, 'create'])->name('create')->middleware('permission:city_stations:create');
        Route::get('/{cityStation:uuid}/edit', [CityStationController::class, 'edit'])->name('edit')->middleware('permission:city_stations:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CityStationController::class, 'store'])->name('store')->middleware('permission:city_stations:create');
        Route::match(['PUT', 'post'], '/{cityStation:uuid}', [CityStationController::class, 'update'])->name('update')->middleware('permission:city_stations:edit');
        Route::delete('/{cityStation:uuid}', [CityStationController::class, 'destroy'])->name('destroy')->middleware('permission:city_stations:delete');
    });
});