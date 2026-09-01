<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\UserController;



Route::controller(UserController::class)->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/bookings', 'bookings')->name('bookings');
        Route::get('/profile', 'profile')->name('profile');
        Route::get('/profile/edit', 'editProfile')->name('profile.edit');
        Route::get('/wallet', 'wallet')->name('wallet.index');
        Route::get('/wallet/transactions', 'transactions')->name('wallet.transactions');
        Route::get('/profile/phone/verify', 'phoneVerify')->name('profile.phone.verify');

    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/profile', 'updateProfile')->name('profile.update');
        Route::post('/profile/phone/send-otp', 'sendPhoneOtp')->name('profile.phone.send-otp');
        Route::post('/profile/phone/verify-otp', 'verifyPhoneOtp')->name('profile.phone.verify-otp');
    });
});