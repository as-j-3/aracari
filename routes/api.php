<?php

use Illuminate\Support\Facades\Route;

    Route::prefix('auth')->group(function () {
        require __DIR__.'/api/auth.php';
    });

    Route::prefix('trips')->group(function () {
        require __DIR__.'/api/trips.php';
    });

    Route::middleware(['auth:sanctum', 'phone.verified'])->prefix('bookings')->group(function () {
        require __DIR__.'/api/bookings.php';
    });

    Route::prefix('ads')->group(function () {
        require __DIR__.'/api/ads.php';
    });

    Route::prefix('lookups')->group(function () {
        require __DIR__.'/api/lookups.php';
    });


    Route::middleware('auth:sanctum')->prefix('files')->group(function () {
        require __DIR__.'/api/files.php';
    });

    Route::middleware('auth:sanctum')->prefix('user')->group(function () {
        require __DIR__.'/api/user.php';
    });

    Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
        require __DIR__.'/api/notifications.php';
    });

    Route::middleware('auth:sanctum')->prefix('currency')->group(function () {
        require __DIR__.'/api/currency.php';
    });
    // ==========================================
// 1. Admin API Routes (مسارات الأدمن)
// ==========================================
Route::middleware(['auth:sanctum', 'verified', 'require.admin'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])
            ->middleware('permission:settings:view');
        Route::post('/settings', [App\Http\Controllers\Admin\SettingController::class, 'update'])
            ->middleware('permission:settings:edit');

        // استدعاء ملفات المسارات المفرعة للوحة الإدارة
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
        require __DIR__.'/web/admin/files.php';
        require __DIR__.'/web/admin/user.php';
});

// ==========================================
// 2. Provider API Routes (مسارات مزودي الخدمة)
// ==========================================
Route::middleware(['auth:sanctum', 'verified', 'require.provider'])
    ->prefix('provider')
    ->group(function () {
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
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
    ], 404);
});
