<?php

use App\Http\Controllers\Admin\TripTypeController;
use App\Http\Controllers\Admin\TripLevelController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\CouponStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('lookups')->name('lookups.')->group(function () {

    Route::controller(TripTypeController::class)->prefix('trip-types')->name('trip-types.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:trip-types:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:trip-types:create');
            Route::get('/{id}/edit', 'edit')->name('edit')->middleware('permission:trip-types:edit'); 
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:trip-types:create');
            Route::match(['put', 'post'], '/{id}', 'update')->name('update')->middleware('permission:trip-types:edit'); 
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:trip-types:delete');  
        });
    });

    Route::controller(TripLevelController::class)->prefix('trip-levels')->name('trip-levels.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:trip-levels:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:trip-levels:create');
            Route::get('/{id}/edit', 'edit')->name('edit')->middleware('permission:trip-levels:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:trip-levels:create');
            Route::match(['put', 'post'], '/{id}', 'update')->name('update')->middleware('permission:trip-levels:edit');
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:trip-levels:delete');
        });
    });

    Route::controller(PaymentMethodController::class)->prefix('payment-methods')->name('payment-methods.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:payment-methods:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:payment-methods:create');
            Route::get('/{id}/edit', 'edit')->name('edit')->middleware('permission:payment-methods:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:payment-methods:create');
            Route::match(['put', 'post'], '/{id}', 'update')->name('update')->middleware('permission:payment-methods:edit');
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:payment-methods:delete');
        });
    });

    Route::controller(CouponStatusController::class)->prefix('coupon-statuses')->name('coupon-statuses.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:coupon-statuses:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:coupon-statuses:create');
            Route::get('/{id}/edit', 'edit')->name('edit')->middleware('permission:coupon-statuses:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:coupon-statuses:create');
            Route::put('/{id}', 'update')->name('update')->middleware('permission:coupon-statuses:edit');
            Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:coupon-statuses:delete');
        });
    });
});