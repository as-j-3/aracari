<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdController;



Route::prefix('ads')->name('ads.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [AdController::class, 'index'])->name('index')->middleware('permission:ads:view');
        Route::get('/create', [AdController::class, 'create'])->name('create')->middleware('permission:ads:create');
        Route::get('/{uuid}/edit', [AdController::class, 'edit'])->name('edit')->middleware('permission:ads:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [AdController::class, 'store'])->name('store')->middleware('permission:ads:create');
        Route::put('/{uuid}', [AdController::class, 'update'])->name('update')->middleware('permission:ads:edit');
        Route::delete('/{uuid}', [AdController::class, 'destroy'])->name('destroy')->middleware('permission:ads:delete');
    });
});