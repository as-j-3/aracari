<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\BookingController;



Route::prefix('bookings')->name('bookings.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index')->middleware('permission:bookings:view');
        Route::get('/{tripUuid}/seats', [BookingController::class, 'seats'])->name('seats')->middleware('permission:bookings:view');
        Route::get('/{uuid}', [BookingController::class, 'show'])->name('show')->middleware('permission:bookings:view');
    });

    Route::middleware(['throttle:5,1'])->group(function () {
        Route::post('/{uuid}/cancel', [BookingController::class, 'cancel'])->name('cancel')->middleware('permission:bookings:cancel');
    });
});
