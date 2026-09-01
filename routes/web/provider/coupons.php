<?php

use App\Http\Controllers\Provider\ProviderCouponController;
use Illuminate\Support\Facades\Route;


Route::prefix('coupons')->name('coupons.')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderCouponController::class, 'index'])->name('index')
            ->middleware('permission:provider:coupons:view');
        Route::get('/create', [ProviderCouponController::class, 'create'])->name('create')
            ->middleware('permission:provider:coupons:create');
        Route::get('/{uuid}', [ProviderCouponController::class, 'edit'])->name('edit')
            ->middleware('permission:provider:coupons:edit');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [ProviderCouponController::class, 'store'])->name('store')
            ->middleware('permission:provider:coupons:create');
        Route::put('/{uuid}', [ProviderCouponController::class, 'update'])->name('update')
            ->middleware('permission:provider:coupons:edit');
        Route::delete('/{uuid}', [ProviderCouponController::class, 'destroy'])->name('destroy')
            ->middleware('permission:provider:coupons:delete');
    });
});
