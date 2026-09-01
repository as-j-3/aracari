<?php

use App\Http\Controllers\Provider\ProviderReportController;
use Illuminate\Support\Facades\Route;



Route::prefix('reports')->name('reports.')
    ->middleware(['permission:provider:reports:view', 'throttle:30,1'])
    ->group(function () {
        Route::get('/revenue', [ProviderReportController::class, 'revenue'])->name('revenue');
        Route::get('/bookings', [ProviderReportController::class, 'bookings'])->name('bookings');
        Route::get('/trips', [ProviderReportController::class, 'trips'])->name('trips');
    });
