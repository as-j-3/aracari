<?php

use App\Http\Controllers\Admin\DigitalPocketController;
use Illuminate\Support\Facades\Route;

Route::controller(DigitalPocketController::class)->prefix('digital-pockets')->name('digital-pockets.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:digital-pockets:view');
        Route::get('/create', 'create')->name('create')->middleware('permission:digital-pockets:create');
        Route::get('/{digitalPocket}/edit', 'edit')->name('edit')->middleware('permission:digital-pockets:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:digital-pockets:create');
        Route::match(['PUT', 'post'], '/{digitalPocket}', 'update')->name('update')->middleware('permission:digital-pockets:edit');
        Route::delete('/{digitalPocket}', 'destroy')->name('destroy')->middleware('permission:digital-pockets:delete');
    });
});