<?php

use App\Http\Controllers\Admin\ExchangeRateController;
use Illuminate\Support\Facades\Route;

Route::controller(ExchangeRateController::class)->prefix('exchange-rates')->name('exchange-rates.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:exchange-rates:view');
        Route::get('/create', 'create')->name('create')->middleware('permission:exchange-rates:create');
        Route::get('/{exchangeRate}/edit', 'edit')->name('edit')->middleware('permission:exchange-rates:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:exchange-rates:create');
        Route::put('/{exchangeRate}', 'update')->name('update')->middleware('permission:exchange-rates:edit');
        Route::delete('/{exchangeRate}', 'destroy')->name('destroy')->middleware('permission:exchange-rates:delete');
    });
});