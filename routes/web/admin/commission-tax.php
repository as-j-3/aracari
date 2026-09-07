<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CommissionTaxController;

Route::prefix('commission-taxes')->name('commission-taxes.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [CommissionTaxController::class, 'index'])->name('index')->middleware('permission:commission-tax:view');
        Route::get('/{commissionTax:uuid}', [CommissionTaxController::class, 'show'])->name('show')->middleware('permission:commission-tax:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CommissionTaxController::class, 'store'])->name('store')->middleware('permission:commission-tax:create');
        Route::match(['PUT', 'post'], '/{commissionTax:uuid}', [CommissionTaxController::class, 'update'])->name('update')->middleware('permission:commission-tax:edit');
        Route::delete('/{commissionTax:uuid}', [CommissionTaxController::class, 'destroy'])->name('destroy')->middleware('permission:commission-tax:delete');
    });
});
