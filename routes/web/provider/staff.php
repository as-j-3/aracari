<?php

use App\Http\Controllers\Provider\ProviderStaffController;
use Illuminate\Support\Facades\Route;



Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderStaffController::class, 'index'])->name('index')
            ->middleware('permission:provider:staff:view');
        Route::get('/create', [ProviderStaffController::class, 'create'])->name('create')
            ->middleware('permission:provider:staff:create');
        Route::get('/{uuid}', [ProviderStaffController::class, 'edit'])->name('edit')
            ->middleware('permission:provider:staff:edit')
            ->where('uuid', '[0-9a-fA-F\-]{36}');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [ProviderStaffController::class, 'store'])->name('store')
            ->middleware('permission:provider:staff:create');
        Route::put('/{uuid}', [ProviderStaffController::class, 'update'])->name('update')
            ->middleware('permission:provider:staff:edit')
            ->where('uuid', '[0-9a-fA-F\-]{36}');
        Route::delete('/{uuid}', [ProviderStaffController::class, 'destroy'])->name('destroy')
            ->middleware('permission:provider:staff:delete')
            ->where('uuid', '[0-9a-fA-F\-]{36}');
    });
});
