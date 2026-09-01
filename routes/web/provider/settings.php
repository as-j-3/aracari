<?php

use App\Http\Controllers\Provider\ProviderSettingsController;
use Illuminate\Support\Facades\Route;



Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [ProviderSettingsController::class, 'index'])->name('index')
        ->middleware(['throttle:30,1', 'permission:provider:settings:view']);

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/profile', [ProviderSettingsController::class, 'updateProfile'])->name('profile.update')
            ->middleware('permission:provider:settings:edit');
        Route::post('/logo', [ProviderSettingsController::class, 'uploadLogo'])->name('logo.upload')
            ->middleware('permission:provider:settings:edit');
        Route::post('/logo/remove', [ProviderSettingsController::class, 'removeLogo'])->name('logo.remove')
            ->middleware('permission:provider:settings:edit');
        Route::post('/banks', [ProviderSettingsController::class, 'storeBankAccount'])->name('banks.store')
            ->middleware('permission:provider:settings:edit');
        Route::put('/banks/{method}', [ProviderSettingsController::class, 'updateBankAccount'])->name('banks.update')
            ->middleware('permission:provider:settings:edit');
        Route::delete('/banks/{method}', [ProviderSettingsController::class, 'destroyBankAccount'])->name('banks.destroy')
            ->middleware('permission:provider:settings:edit');
    });
});
