<?php

use App\Http\Controllers\Admin\TaxRuleController;
use Illuminate\Support\Facades\Route;



Route::prefix('tax-rules')->name('tax-rules.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [TaxRuleController::class, 'index'])->name('index')->middleware('permission:tax-rules:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [TaxRuleController::class, 'store'])->name('store')->middleware('permission:tax-rules:create');
        Route::put('/{taxRule}', [TaxRuleController::class, 'update'])->name('update')->middleware('permission:tax-rules:edit');
        Route::delete('/{taxRule}', [TaxRuleController::class, 'destroy'])->name('destroy')->middleware('permission:tax-rules:delete');
    });
});
