<?php

use App\Http\Controllers\Provider\ProviderDashboardController;
use Illuminate\Support\Facades\Route;



Route::get('/dashboard', [ProviderDashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(['throttle:30,1', 'permission:provider:dashboard:view']);
