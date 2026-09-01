<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LookupController;

Route::middleware('throttle:30,1')->controller(LookupController::class)->group(function () {
    Route::get('/genders','genders');
    Route::get('/cities','cities');
    Route::get('/trip-locations',  'tripLocations');
    Route::get('/payment-methods', 'paymentMethods');
    Route::get('/amenities', 'amenities');
    Route::get('/trip-types', 'tripTypes');
    Route::get('/service-types', 'serviceTypes');
    Route::get('/country-codes', 'countryCodes');
    Route::post('/validate-coupon',  'validateCoupon')
        ->middleware('throttle:15,1');
    Route::post('/upcoming-trips', 'upcomingTripsByCountry');
});
