<?php

use App\Http\Controllers\Provider\ProviderBookingController;
use Illuminate\Support\Facades\Route;



Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [ProviderBookingController::class, 'allIndex'])->name('index')
        ->middleware(['throttle:30,1', 'permission:provider:bookings:view']);
});
