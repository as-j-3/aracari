<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CityController;

Route::prefix('cities')->name('cities.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [CityController::class, 'index'])->name('index')->middleware('permission:cities:view');
        Route::get('/create', [CityController::class, 'create'])->name('create')->middleware('permission:cities:create');
        Route::get('/{city:uuid}/edit', [CityController::class, 'edit'])->name('edit')->middleware('permission:cities:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CityController::class, 'store'])->name('store')->middleware('permission:cities:create');
        Route::match(['PUT', 'post'], '/{city:uuid}', [CityController::class, 'update'])->name('update')->middleware('permission:cities:edit');
        Route::delete('/{city:uuid}', [CityController::class, 'destroy'])->name('destroy')->middleware('permission:cities:delete');
    });
});