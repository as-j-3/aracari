<?php

use App\Http\Controllers\Admin\ProviderServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('provider-services')->name('provider-services.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderServiceController::class, 'index'])->name('index')->middleware('permission:providers:view');
        Route::get('/create', [ProviderServiceController::class, 'create'])->name('create')->middleware('permission:providers:create');
        Route::get('/digital-pockets', [ProviderServiceController::class, 'digitalPockets'])->name('digital-pockets')->middleware('permission:providers:create');
        Route::get('/{uuid}', [ProviderServiceController::class, 'show'])->name('show')->middleware('permission:providers:view');
        Route::get('/{uuid}/edit', [ProviderServiceController::class, 'edit'])->name('edit')->middleware('permission:providers:edit');
        Route::get('/{uuid}/activity', [ProviderServiceController::class, 'activity'])->name('activity')->middleware('permission:providers:view');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [ProviderServiceController::class, 'store'])->name('store')->middleware('permission:providers:create');
        Route::match(['put', 'post'], '/{uuid}', [ProviderServiceController::class, 'update'])->name('update')->middleware('permission:providers:edit');
        Route::delete('/{uuid}', [ProviderServiceController::class, 'destroy'])->name('destroy')->middleware('permission:providers:delete');
        Route::post('/{uuid}/toggle-status', [ProviderServiceController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:providers:edit');
        Route::post('/{uuid}/files', [ProviderServiceController::class, 'uploadFile'])->name('files.upload')->middleware('permission:providers:edit');
        Route::delete('/{uuid}/files/{fileId}', [ProviderServiceController::class, 'deleteFile'])->name('files.delete')->middleware('permission:providers:edit');
        Route::post('/{uuid}/files/{fileId}/toggle', [ProviderServiceController::class, 'toggleFileStatus'])->name('files.toggle')->middleware('permission:providers:edit');
    });
});