<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
// use App\Http\Controllers\API\BookingModificationController;



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('api.bookings.index');
    Route::get('/{uuid}', [BookingController::class, 'show'])->name('api.bookings.show');
});

Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::post('/round-trip', [BookingController::class, 'bookRoundTrip'])
        ->name('api.bookings.round-trip')
        ->middleware('throttle:10,1');

    Route::post('/{tripUuid}', [BookingController::class, 'store'])
        ->name('api.bookings.store')
        ->middleware('throttle:10,1');
        
    Route::post('/tickets/{ticketUuid}/board', [BookingController::class, 'confirmBoarding'])
        ->name('api.tickets.board')
        ->where('ticketUuid', '[0-9a-fA-F\-]{36}')
        ->middleware(['permission:bookings:board', 'throttle:30,1']);

    Route::get('/tickets/boarded', [BookingController::class, 'myBoardedTickets'])
        ->name('api.tickets.boarded')
        ->middleware('permission:bookings:view');
});

Route::middleware(['auth:sanctum', 'permission:bookings:edit'])->group(function () {
    
    Route::put('/{uuid}', [BookingController::class, 'update'])
        ->name('api.bookings.update')
        ->middleware('throttle:5,1');
    
});

Route::middleware(['auth:sanctum', 'permission:bookings:cancel'])->group(function () {
    
    Route::post('/{uuid}/cancel', [BookingController::class, 'cancel'])
        ->name('api.bookings.cancel')
        ->middleware('throttle:5,1');

});
