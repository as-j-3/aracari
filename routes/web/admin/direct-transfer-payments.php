<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DirectTransferPaymentController;

Route::prefix('direct-transfer')->name('direct-transfer.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [DirectTransferPaymentController::class, 'index'])->name('index')->middleware('permission:direct-transfer-payment:view');
        Route::get('/{uuid}', [DirectTransferPaymentController::class, 'show'])->name('show')->middleware('permission:direct-transfer-payment:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [DirectTransferPaymentController::class, 'store'])->name('store')->middleware('permission:direct-transfer-payment:create');
        Route::match(['put', 'post'], '/{uuid}', [DirectTransferPaymentController::class, 'update'])->name('update')->middleware('permission:direct-transfer-payment:edit');
        Route::delete('/{uuid}', [DirectTransferPaymentController::class, 'destroy'])->name('destroy')->middleware('permission:direct-transfer-payment:delete');
    });

});