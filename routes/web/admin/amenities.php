<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AmenityController;

Route::prefix('amenities')->name('amenities.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [AmenityController::class, 'index'])->name('index')->middleware('permission:amenities:view');
        Route::get('/create', [AmenityController::class, 'create'])->name('create')->middleware('permission:amenities:create');
        Route::get('/{amenity:uuid}/edit', [AmenityController::class, 'edit'])->name('edit')->middleware('permission:amenities:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [AmenityController::class, 'store'])->name('store')->middleware('permission:amenities:create');
        Route::match(['put', 'post'], '/{amenity:uuid}', [AmenityController::class, 'update'])->name('update')->middleware('permission:amenities:edit');
        Route::delete('/{amenity:uuid}', [AmenityController::class, 'destroy'])->name('destroy')->middleware('permission:amenities:delete');
    });
});