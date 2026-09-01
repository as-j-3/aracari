<?php

use App\Http\Controllers\Provider\ProviderBookingController;
use App\Http\Controllers\Provider\ProviderBranchController;
use App\Http\Controllers\Provider\ProviderTripController;
use Illuminate\Support\Facades\Route;



Route::prefix('branches')->name('branches.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderBranchController::class, 'index'])->name('index')
            ->middleware('permission:provider:branches:view');

        Route::get('/create', [ProviderBranchController::class, 'create'])->name('create')
            ->middleware('permission:provider:branches:create');

        Route::get('/{branch:uuid}', [ProviderBranchController::class, 'show'])->name('show')
            ->middleware('permission:provider:branches:view');

        Route::get('/{branch:uuid}/edit', [ProviderBranchController::class, 'edit'])->name('edit')
            ->middleware('permission:provider:branches:edit');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [ProviderBranchController::class, 'store'])->name('store')
            ->middleware('permission:provider:branches:create');

        Route::put('/{branch:uuid}', [ProviderBranchController::class, 'update'])->name('update')
            ->middleware('permission:provider:branches:edit');

        Route::post('/{branch:uuid}/toggle-status', [ProviderBranchController::class, 'toggleStatus'])->name('toggle-status')
            ->middleware('permission:provider:branches:edit');

        Route::delete('/{branch:uuid}', [ProviderBranchController::class, 'destroy'])->name('destroy')
            ->middleware('permission:provider:branches:delete');
    });

    Route::prefix('{branch:uuid}/staff')->name('staff.')->group(function () {
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', [ProviderBranchController::class, 'addStaff'])->name('store')
                ->middleware('branch.permission:provider:branches:staff:add');
            Route::delete('/{userId}', [ProviderBranchController::class, 'removeStaff'])->name('destroy')
                ->middleware('branch.permission:provider:branches:staff:remove');
        });
    });

    Route::prefix('{branch:uuid}/bookings')->name('bookings.')->group(function () {
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', [ProviderBookingController::class, 'index'])->name('index')
                ->middleware('branch.permission:provider:branches:bookings:view');
            Route::get('/{booking:uuid}', [ProviderBookingController::class, 'show'])->name('show')
                ->middleware('branch.permission:provider:branches:bookings:view');
        });

        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', [ProviderBookingController::class, 'store'])->name('store')
                ->middleware('branch.permission:provider:branches:bookings:create');
            Route::post('/{booking:uuid}/cancel', [ProviderBookingController::class, 'cancel'])->name('cancel')
                ->middleware('branch.permission:provider:branches:bookings:cancel');
        });
    });

    Route::prefix('{branch:uuid}/trips')->name('trips.')->group(function () {
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', [ProviderTripController::class, 'index'])->name('index')
                ->middleware('branch.permission:provider:branches:trips:view');
            Route::get('/create', [ProviderTripController::class, 'create'])->name('create')
                ->middleware('branch.permission:provider:branches:trips:create');
            Route::get('/{trip:uuid}', [ProviderTripController::class, 'edit'])->name('edit')
                ->middleware('branch.permission:provider:branches:trips:edit');
        });

        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', [ProviderTripController::class, 'store'])->name('store')
                ->middleware('branch.permission:provider:branches:trips:create');
            Route::put('/{trip:uuid}', [ProviderTripController::class, 'update'])->name('update')
                ->middleware('branch.permission:provider:branches:trips:edit');
            Route::delete('/{trip:uuid}', [ProviderTripController::class, 'destroy'])->name('destroy')
                ->middleware('branch.permission:provider:branches:trips:delete');
        });
    });
});
