<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdController;



// ==================== Public Routes (لا تحتاج تسجيل دخول) ====================
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/', [AdController::class, 'index'])
        ->name('api.ads.index');
    
    Route::get('/{uuid}', [AdController::class, 'show'])
        ->name('api.ads.show');
});
