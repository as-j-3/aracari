<?php

use App\Http\Controllers\Admin\CountryTaxController;
use App\Http\Controllers\Admin\TaxRuleController;
use Illuminate\Support\Facades\Route;



Route::prefix('country-tax')->name('country-tax.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [CountryTaxController::class, 'index'])->name('index')->middleware('permission:country-tax:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CountryTaxController::class, 'store'])->name('store')->middleware('permission:country-tax:create');
        Route::match(['PUT', 'post'], '/{countryTax:uuid}', [CountryTaxController::class, 'update'])->name('update')->middleware('permission:country-tax:edit');
        Route::delete('/{countryTax:uuid}', [CountryTaxController::class, 'destroy'])->name('destroy')->middleware('permission:country-tax:delete');
    });
});
