<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CountryController;

Route::prefix('countries')->name('countries.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [CountryController::class, 'index'])->name('index')->middleware('permission:countries:view');
        Route::get('/create', [CountryController::class, 'create'])->name('create')->middleware('permission:countries:create');
        Route::get('/{country:uuid}/edit', [CountryController::class, 'edit'])->name('edit')->middleware('permission:countries:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CountryController::class, 'store'])->name('store')->middleware('permission:countries:create');
        Route::match(['PUT', 'post'], '/{country:uuid}', [CountryController::class, 'update'])->name('update')->middleware('permission:countries:edit');
        Route::delete('/{country:uuid}', [CountryController::class, 'destroy'])->name('destroy')->middleware('permission:countries:delete');
    });
});