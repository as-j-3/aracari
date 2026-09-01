<?php

use App\Http\Controllers\Admin\PassengerController;
use Illuminate\Support\Facades\Route;

Route::prefix('passengers')->name('passengers.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [PassengerController::class, 'index'])->name('index')->middleware('permission:passengers:view');
        Route::get('/create', [PassengerController::class, 'create'])->name('create')->middleware('permission:passengers:create');
        Route::get('/{passenger:uuid}', [PassengerController::class, 'show'])->name('show')->middleware('permission:passengers:view');
        Route::get('/{passenger:uuid}/edit', [PassengerController::class, 'edit'])->name('edit')->middleware('permission:passengers:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [PassengerController::class, 'store'])->name('store')->middleware('permission:passengers:create');
        Route::put('/{passenger:uuid}', [PassengerController::class, 'update'])->name('update')->middleware('permission:passengers:edit');
        Route::delete('/{passenger:uuid}', [PassengerController::class, 'destroy'])->name('destroy')->middleware('permission:passengers:delete');
    });
});