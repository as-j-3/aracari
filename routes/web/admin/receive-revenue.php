<?php

use App\Http\Controllers\Admin\ReceiveRevenueController;
use Illuminate\Support\Facades\Route;

Route::controller(ReceiveRevenueController::class)->prefix('receive-revenue')->name('receive-revenue.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:receive-revenue:view');
        Route::get('/create', 'create')->name('create')->middleware('permission:receive-revenue:create');
        Route::get('/{receiveRevenue}/edit', 'edit')->name('edit')->middleware('permission:receive-revenue:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:receive-revenue:create');
        Route::put('/{receiveRevenue}', 'update')->name('update')->middleware('permission:receive-revenue:edit');
        Route::delete('/{receiveRevenue}', 'destroy')->name('destroy')->middleware('permission:receive-revenue:delete');
    });
});