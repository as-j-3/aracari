<?php

use App\Http\Controllers\Provider\ProviderPolicyController;
use Illuminate\Support\Facades\Route;



Route::prefix('policies')->name('policies.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderPolicyController::class, 'index'])->name('index')
            ->middleware('permission:provider:policies:view');
        Route::get('/{policy:uuid}/json', [ProviderPolicyController::class, 'showJson'])->name('show.json')
            ->middleware('permission:provider:policies:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [ProviderPolicyController::class, 'store'])->name('store')
            ->middleware('permission:provider:policies:create');
        Route::put('/{policy:uuid}', [ProviderPolicyController::class, 'update'])->name('update')
            ->middleware('permission:provider:policies:edit');
        Route::delete('/{policy:uuid}', [ProviderPolicyController::class, 'destroy'])->name('destroy')
            ->middleware('permission:provider:policies:delete');
    });
});
