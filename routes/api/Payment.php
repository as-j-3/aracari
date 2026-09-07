<?php

use App\Http\Controllers\Api\DirectTransferPaymentController;
use App\Http\Controllers\Api\UserDirectPaymentInfoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/direct-transfers', [DirectTransferPaymentController::class, 'index'])
        ->name('direct-transfers.index');
    Route::post('/direct-transfers/info', [
        UserDirectPaymentInfoController::class,
        'store'
    ])->name('direct-transfers.info.store');
});
