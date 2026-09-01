<?php

use App\Http\Controllers\Provider\ProviderTripController;
use Illuminate\Support\Facades\Route;



Route::prefix('trips')->name('trips.')->group(function () {
    Route::get('/', [ProviderTripController::class, 'allIndex'])->name('index')
        ->middleware(['throttle:30,1', 'permission:provider:trips:view']);

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/{trip:uuid}/stop-recurrence', [ProviderTripController::class, 'stopRecurrence'])->name('stop-recurrence')
            ->middleware('permission:provider:branches:trips:edit');
    });
});
