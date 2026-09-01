<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;



Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/', [NotificationController::class, 'index'])
        ->name('api.notifications')
        ->middleware('throttle:60,1');

    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('api.notifications.unread-count')
        ->middleware('throttle:120,1');

    Route::patch('/{id}/read', [NotificationController::class, 'markRead'])
        ->name('api.notifications.mark-read')
        ->middleware('throttle:60,1');

    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('api.notifications.mark-all-read')
        ->middleware('throttle:10,1');

    Route::delete('/{id}', [NotificationController::class, 'destroy'])
        ->name('api.notifications.destroy')
        ->middleware('throttle:30,1');
});
