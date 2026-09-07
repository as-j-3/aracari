<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProviderTaxController;

Route::prefix('provider-taxes')->name('provider-taxes.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderTaxController::class, 'index'])->name('index')->middleware('permission:provider-tax:view');
        Route::get('/{providerTax:uuid}', [ProviderTaxController::class, 'show'])->name('show')->middleware('permission:provider-tax:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [ProviderTaxController::class, 'store'])->name('store')->middleware('permission:provider-tax:create');
        Route::match(['PUT', 'post'], '/{providerTax:uuid}', [ProviderTaxController::class, 'update'])->name('update')->middleware('permission:provider-tax:edit');
        Route::delete('/{providerTax:uuid}', [ProviderTaxController::class, 'destroy'])->name('destroy')->middleware('permission:provider-tax:delete');
    });
});
