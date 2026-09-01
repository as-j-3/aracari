<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CouponController;

Route::prefix('coupons')->name('coupons.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index')->middleware('permission:coupons:view');
        Route::get('/create', [CouponController::class, 'create'])->name('create')->middleware('permission:coupons:create');
        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit')->middleware('permission:coupons:edit');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CouponController::class, 'store'])->name('store')->middleware('permission:coupons:create');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update')->middleware('permission:coupons:edit');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy')->middleware('permission:coupons:delete');
    });
});