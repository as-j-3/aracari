<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TypeOfServiceController;

Route::prefix('service-types')->name('service-types.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [TypeOfServiceController::class, 'index'])->name('index')->middleware('permission:service-types:view');
        Route::get('/create', [TypeOfServiceController::class, 'create'])->name('create')->middleware('permission:service-types:create');
        Route::get('/{serviceType}', [TypeOfServiceController::class, 'show'])->name('show')->middleware('permission:service-types:view');
        Route::get('/{serviceType}/edit', [TypeOfServiceController::class, 'edit'])->name('edit')->middleware('permission:service-types:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [TypeOfServiceController::class, 'store'])->name('store')->middleware('permission:service-types:create');
        Route::put('/{serviceType}', [TypeOfServiceController::class, 'update'])->name('update')->middleware('permission:service-types:edit');
        Route::delete('/{serviceType}', [TypeOfServiceController::class, 'destroy'])->name('destroy')->middleware('permission:service-types:delete');
    });
});