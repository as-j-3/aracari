<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Admin\SettingController;

$maintenanceBypass = filter_var(config('app.maintenance_bypass', false), FILTER_VALIDATE_BOOL);

if ($maintenanceBypass) {
    $allowedIps = array_filter(array_map('trim', explode(',', config('app.maintenance_bypass_ips', '127.0.0.1'))));
    
    Route::get('/maintenance-bypass-active', function () use ($allowedIps) {
        if (!in_array(request()->getClientIp(), $allowedIps)) {
            abort(403, 'Access denied');
        }
        
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Access denied');
        }
        
        return response('Maintenance bypass is active.', 200);
    })->name('maintenance.bypass');
}
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::controller(HomeController::class)->middleware('throttle:30,1')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/search', 'search')->name('home.search');
    Route::get('/trip/{uuid}', 'show')->name('home.show');
});

Route::name('pages.')->middleware('throttle:30,1')->group(function () {
    Route::get('/about', function () {
        return view('pages.about');
    })->name('about');

    Route::get('/contact', function () {
        return view('pages.contact');
    })->name('contact');

    Route::get('/faq', function () {
        return view('pages.faq');
    })->name('faq');

    Route::get('/terms', function () {
        return view('pages.terms');
    })->name('terms');
});

Route::controller(AuthController::class)->group(function () {
    Route::middleware('guest')->group(function () {

        Route::middleware('throttle:10,1')->group(function () {
            Route::get('/login', 'showLoginForm')->name('login');
            Route::get('/register', 'showRegistrationForm')->name('register');
            Route::get('/forgot-password', 'showForgotPasswordForm')->name('password.request');
        });
        Route::post('/login', 'login')->middleware('throttle:5,1');
        Route::post('/register', 'register')->middleware('throttle:5,1'); 
        Route::get('/verify/{uuid}', 'showVerificationForm')->name('verify')->middleware('throttle:10,1');
        Route::post('/verify', 'verifyOtp')->name('verify.otp')->middleware('throttle:3,1');
        Route::post('/resend-otp', 'resendOtp')->name('resend.otp')->middleware('throttle:2,1');       
        Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:2,1');
        Route::get('/reset-password', 'showResetPasswordForm')->name('password.reset');
        Route::post('/reset-password', 'resetPassword')->name('password.update')->middleware('throttle:10,1');
        Route::get('/set-password/{uuid}', 'showSetPasswordForm')->name('password.set');
        Route::post('/set-password', 'setPassword')->name('password.set.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', 'logout')->name('logout')->middleware('throttle:10,1');
        Route::post('/change-password', 'changePassword')->name('password.change')->middleware('throttle:3,1');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/web/admin/files.php';
    require __DIR__.'/web/admin/user.php';
});

Route::middleware(['auth', 'verified', 'phone.verified'])->group(function () {
    require __DIR__.'/web/user/bookings.php';
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::middleware('require.admin')->group(function () {
            Route::get('/settings', [SettingController::class, 'index'])
                ->name('settings')
                ->middleware('permission:settings:view');
            Route::post('/settings', [SettingController::class, 'update'])
                ->name('settings.update')
                ->middleware('permission:settings:edit');
                
            require __DIR__.'/web/admin/tax-rules.php';
            require __DIR__.'/web/admin/ads.php';
            require __DIR__.'/web/admin/admin.php';
            require __DIR__.'/web/admin/lookups.php';
            require __DIR__.'/web/admin/vehicles.php';
            require __DIR__.'/web/admin/cities.php';
            require __DIR__.'/web/admin/amenities.php';
            require __DIR__.'/web/admin/coupons.php';
            require __DIR__.'/web/admin/vehicle_types.php';
            require __DIR__.'/web/admin/countries.php';
            require __DIR__.'/web/admin/taxes.php';
            require __DIR__.'/web/admin/trips.php';
            require __DIR__.'/web/admin/bookings.php';
            require __DIR__.'/web/admin/service_types.php';
            require __DIR__.'/web/admin/provider_branches.php';
            require __DIR__.'/web/admin/reports.php';
            require __DIR__.'/web/admin/accounting.php';
            require __DIR__.'/web/admin/roles.php';
            require __DIR__.'/web/admin/services.php';
            require __DIR__.'/web/admin/provider-services.php';
            require __DIR__.'/web/admin/passengers.php';
            require __DIR__.'/web/admin/exchange-rates.php';
            require __DIR__.'/web/admin/receive-revenue.php';
            require __DIR__.'/web/admin/digital-pockets.php';
            require __DIR__.'/web/admin/currencies.php';
            require __DIR__.'/web/admin/city-stations.php';
        });
    });
        Route::middleware(['require.provider','auth', 'verified'])->prefix('provider')->name('provider.')->group(function () {
            require __DIR__.'/web/provider/dashboard.php';
            require __DIR__.'/web/provider/trips.php';
            require __DIR__.'/web/provider/bookings.php';
            require __DIR__.'/web/provider/reports.php';
            require __DIR__.'/web/provider/staff.php';
            require __DIR__.'/web/provider/branches.php';
            require __DIR__.'/web/provider/vehicles.php';
            require __DIR__.'/web/provider/policies.php';
            require __DIR__.'/web/provider/coupons.php';
            require __DIR__.'/web/provider/settings.php';
        });
