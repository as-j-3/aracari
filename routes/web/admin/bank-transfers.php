<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DirectTransferPaymentAdminController;

Route::prefix('bank-transfers')->name('admin.direct-transfer-payments.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [DirectTransferPaymentAdminController::class, 'index'])->name('index')->middleware('permission:booking-payments:view');
        Route::get('/{uuid}', [DirectTransferPaymentAdminController::class, 'show'])->name('show')->middleware('permission:booking-payments:view');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/{uuid}/approve', [DirectTransferPaymentAdminController::class, 'approve'])->name('approve')->middleware('permission:booking-payments:edit');
        Route::post('/{uuid}/reject', [DirectTransferPaymentAdminController::class, 'reject'])->name('reject')->middleware('permission:booking-payments:edit');
    });

});