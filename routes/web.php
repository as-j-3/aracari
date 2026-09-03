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

// ==========================================
// 1. مسارات تحتاج تسجيل دخول وتأكيد حساب عادي
// ==========================================
// تشترط أن يكون المستخدم مسجل دخوله (`auth`) وحسابه مفعل (`verified`)
Route::middleware(['auth', 'verified'])->group(function () {
    // استدعاء مسارات إدارة الملفات العامة
    require __DIR__.'/web/admin/files.php';
    
    // استدعاء مسارات إدارة بيانات وتفاصيل المستخدم
    require __DIR__.'/web/admin/user.php';
});

// ==========================================
// 2. مسارات الحجوزات الخاصة بالعملاء
// ==========================================
// تشترط تسجيل الدخول، تأكيد الحساب، وتأكيد رقم الهاتف إضافياً (`phone.verified`)
Route::middleware(['auth', 'verified', 'phone.verified'])->group(function () {
    // استدعاء مسارات حجوزات المستخدم/العميل
    require __DIR__.'/web/user/bookings.php';
});

// ==========================================
// 3. مسارات لوحة تحكم الإدارة (Admin Panel)
// ==========================================
// المجموعة العامة للإدارة: توحد البادئة إلى `/admin/` وأسماء المسارات إلى `admin.`
Route::middleware(['auth', 'verified'])
    ->prefix('admin') // يضيف /admin قبل كل رابط (مثال: example.com/admin/settings)
    ->name('admin.')  // يضيف admin. قبل اسم كل مسار (مثال: route('admin.settings'))
    ->group(function () {

        // طبقة حماية إضافية: تشترط أن يكون المستخدم يملك صلاحية مدير نظام (`require.admin`)
        Route::middleware('require.admin')->group(function () {
            
            // عرض صفحة الإعدادات (يتطلب صلاحية عرض الإعدادات)
            Route::get('/settings', [SettingController::class, 'index'])
                ->name('settings')
                ->middleware('permission:settings:view');

            // تحديث الإعدادات (يتطلب صلاحية تعديل الإعدادات)
            Route::post('/settings', [SettingController::class, 'update'])
                ->name('settings.update')
                ->middleware('permission:settings:edit');
                
            // --- استدعاء ملفات مسارات الإدارة المتخصصة ---
            require __DIR__.'/web/admin/tax-rules.php';        // قواعد الضرائب
            require __DIR__.'/web/admin/ads.php';              // الإعلانات
            require __DIR__.'/web/admin/admin.php';            // إدارة المدراء
            require __DIR__.'/web/admin/lookups.php';          // القوائم المرجعية/الثوابت
            require __DIR__.'/web/admin/vehicles.php';         // المركبات
            require __DIR__.'/web/admin/cities.php';           // المدن
            require __DIR__.'/web/admin/amenities.php';        // الخدمات والوسائل المتاحة
            require __DIR__.'/web/admin/coupons.php';          // كبونات الخصم
            require __DIR__.'/web/admin/vehicle_types.php';    // أنواع المركبات
            require __DIR__.'/web/admin/countries.php';        // الدول
            require __DIR__.'/web/admin/taxes.php';            // الضرائب
            require __DIR__.'/web/admin/trips.php';            // الرحلات
            require __DIR__.'/web/admin/bookings.php';         // حجوزات الإدارة
            require __DIR__.'/web/admin/service_types.php';    // أنواع الخدمات
            require __DIR__.'/web/admin/provider_branches.php';// فروع مزودي الخدمات
            require __DIR__.'/web/admin/reports.php';          // التقارير
            require __DIR__.'/web/admin/accounting.php';       // المحاسبة والشجرة المالية
            require __DIR__.'/web/admin/roles.php';            // الأدوار والصلاحيات (Spatie / Roles)
            require __DIR__.'/web/admin/services.php';         // الخدمات
            require __DIR__.'/web/admin/provider-services.php';// خدمات مزودي الخدمة
            require __DIR__.'/web/admin/passengers.php';       // الركاب/العملاء
            require __DIR__.'/web/admin/exchange-rates.php';   // أسعار الصرف
            require __DIR__.'/web/admin/receive-revenue.php';  // توريد/استلام الإيرادات
            require __DIR__.'/web/admin/digital-pockets.php';  // المحافظ الرقمية
            require __DIR__.'/web/admin/currencies.php';       // العملات
            require __DIR__.'/web/admin/city-stations.php';    // محطات المدن/الانطلاق
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
