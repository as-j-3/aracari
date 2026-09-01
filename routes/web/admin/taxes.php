<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TaxController;

Route::prefix('taxes')->name('taxes.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [TaxController::class, 'index'])->name('index')->middleware('permission:taxes:view');
        Route::get('/create', [TaxController::class, 'create'])->name('create')->middleware('permission:taxes:create');
        Route::get('/{tax:uuid}/edit', [TaxController::class, 'edit'])->name('edit')->middleware('permission:taxes:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [TaxController::class, 'store'])->name('store')->middleware('permission:taxes:create');
        Route::put('/{tax:uuid}', [TaxController::class, 'update'])->name('update')->middleware('permission:taxes:edit');
        Route::delete('/{tax:uuid}', [TaxController::class, 'destroy'])->name('destroy')->middleware('permission:taxes:delete');
    });
});