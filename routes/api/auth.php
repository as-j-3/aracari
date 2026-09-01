<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ProviderAuthController;



Route::controller(AuthController::class)->group(function () {

    Route::post('/register', 'register')
        ->name('api.register')
        ->middleware('throttle:5,1');
    
    Route::post('/login', 'login')
        ->name('api.login')
        ->middleware('throttle:5,1');

    Route::post('/verify-otp', 'verifyOtp')
        ->name('api.verify-otp')
        ->middleware('throttle:3,1');
    
    Route::post('/resend-otp', 'resendOtp')
        ->name('api.resend-otp')
        ->middleware('throttle:2,1')
        ->defaults('purpose', 'verify_account');
    
    Route::post('/resend-password-reset', 'resendOtp')
        ->name('api.resend-password-reset')
        ->middleware('throttle:10,1')
        ->defaults('purpose', 'reset_password');

    Route::post('/forgot-password', 'forgotPassword')
        ->name('api.forgot-password')
        ->middleware('throttle:5,1');
    
    Route::post('/activate-password', 'activatePassword')
        ->name('api.activate-password')
        ->middleware('throttle:5,1');
    
    Route::post('/verify', 'verifyPasswordResetOtp')
        ->name('api.verify-password-reset')
        ->middleware('throttle:5,1');
    
    Route::post('/reset-password', 'resetPassword')
        ->name('api.reset-password')
        ->middleware('throttle:3,1');
});

Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::post('/device-token', [DeviceTokenController::class, 'store'])
        ->name('api.device-token.store')
        ->middleware('throttle:5,1');
    
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')
            ->name('api.logout')
            ->middleware('throttle:10,1');
        
        Route::post('/change-password', 'changePassword')
            ->name('api.change-password')
            ->middleware('throttle:5,1');
    });
});
Route::controller(ProviderAuthController::class)->group(function () {
    
    Route::post('/provider/login', 'login')
        ->name('api.provider.login')
        ->middleware('throttle:5,1');
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/provider/logout', 'logout')->name('api.provider.logout');
        Route::get('/auth/provider/profile', 'getProfile')->name('api.provider.profile');
        });
    });
