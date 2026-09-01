<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;



Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/profile', [UserController::class, 'showMe'])->name('api.profile');
        Route::get('/wallet', [UserController::class, 'wallet'])->name('api.wallet');
    });
        Route::put('/profile', [UserController::class, 'updateProfile'])
        ->name('api.profile.update')
        ->middleware('throttle:10,1'); 
    
    Route::get('/wallet/transactions', [UserController::class, 'transactions'])
        ->name('api.wallet.transactions')
        ->middleware('throttle:30,1');
});