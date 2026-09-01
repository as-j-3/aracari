<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\BookingController;


Route::prefix('bookings')->name('bookings.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/create', [BookingController::class, 'create'])->name('create');
        Route::get('/book/{uuid}', [BookingController::class, 'bookTrip'])->name('book');
        Route::get('/search-return-trips', [BookingController::class, 'searchReturnTrips'])->name('search-return-trips');
        Route::get('/round-trip/{trip:uuid}', [BookingController::class, 'createRoundTrip'])->name('round-trip.create');

        Route::get('/{tripUuid}/seats', [BookingController::class, 'seats'])->name('seats')->middleware('permission:bookings:view');
        Route::get('/{uuid}/print', [BookingController::class, 'print'])->name('print');

        Route::get('/{uuid}', [BookingController::class, 'show'])->name('show');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/{tripUuid}/store', [BookingController::class, 'store'])->name('store');
        Route::post('/round-trip', [BookingController::class, 'bookRoundTrip'])->name('round-trip');
    });
    
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/{uuid}/cancel', [BookingController::class, 'cancel'])->name('cancel')->middleware('permission:bookings:cancel');
    });
});
