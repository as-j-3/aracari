<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CurrencyController;



Route::controller(CurrencyController::class)->group(function () {
    
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/list', 'currencies')->name('api.currencies.list');
    });
    
    Route::post('/convert', 'convert')
        ->name('api.currencies.convert')
        ->middleware('throttle:30,1');
    
    Route::post('/convert-all', 'convertToAll')
        ->name('api.currencies.convert-all')
        ->middleware('throttle:10,1'); 
});