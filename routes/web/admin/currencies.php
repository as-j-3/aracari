<?php

use App\Http\Controllers\Admin\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::controller(CurrencyController::class)->prefix('currencies')->name('currencies.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:currencies:view');
        Route::get('/create', 'create')->name('create')->middleware('permission:currencies:create');
        Route::get('/{currency}/edit', 'edit')->name('edit')->middleware('permission:currencies:edit');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:currencies:create');
        Route::put('/{currency}', 'update')->name('update')->middleware('permission:currencies:edit');
        Route::delete('/{currency}', 'destroy')->name('destroy')->middleware('permission:currencies:delete');
    });
});