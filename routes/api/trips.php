<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\TripTaxController;



Route::middleware('throttle:30,1')->group(function () {
    Route::get('/', [TripController::class, 'index'])->name('api.trips.index');
    Route::get('/available', [TripController::class, 'available'])->name('api.trips.available');
    Route::get('/{uuid}', [TripController::class, 'show'])->name('api.trips.show');
    Route::get('/{uuid}/availability', [TripController::class, 'checkAvailability'])->name('api.trips.availability');
    Route::get('/{uuid}/seats', [BookingController::class, 'seats'])->name('api.trips.seats');
});

Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::post('/taxes/calculate', [TripTaxController::class, 'calculate'])
        ->name('api.taxes.calculate')
        ->middleware('throttle:20,1');
    

    Route::post('/{uuid}/book', [BookingController::class, 'store'])
        ->name('api.trips.book')
        ->middleware(['throttle:10,1']);
        
});