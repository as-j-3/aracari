<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmergencyTripController;
use App\Http\Controllers\Admin\LoginActivityController;
use App\Http\Controllers\Admin\PolicyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ProviderSettlementController;
use App\Http\Controllers\Admin\SecurityIncidentController;


Route::middleware(['auth', 'verified'])->group(function () {
    
    // ==================== Dashboard ====================
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard:access');

    // ==================== Users Management ====================
    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:users:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:users:create');
            Route::get('/{uuid}', 'show')->name('show')->middleware('permission:users:view');
            Route::get('/{uuid}/edit', 'edit')->name('edit')->middleware('permission:users:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:users:create');
            Route::put('/{uuid}', 'update')->name('update')->middleware('permission:users:edit');
            Route::delete('/{uuid}', 'destroy')->name('destroy')->middleware('permission:users:delete');
            Route::post('/{uuid}/approve', 'approve')->name('approve')->middleware('permission:users:approve');
            Route::post('/{uuid}/restore', 'restore')->name('restore')->middleware('permission:users:delete');
            Route::post('/{uuid}/verify-phone', [UserController::class, 'verifyPhone'])->name('verify-phone');
        });
    });

    // ==================== Policies Management ====================
    Route::controller(PolicyController::class)->prefix('policies')->name('policies.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:policies:view');
            Route::get('/{uuid}/details', 'showJson')->name('show.json')->middleware('permission:policies:view');
            Route::get('/list/json', 'getPoliciesList')->name('list.json')->middleware('permission:policies:view');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:policies:create');
            Route::put('/{uuid}', 'update')->name('update')->middleware('permission:policies:edit');
            Route::delete('/{uuid}', 'destroy')->name('destroy')->middleware('permission:policies:delete');
            Route::post('/calculate-fee', 'calculateCancellationFee')->name('calculate-fee')->middleware('permission:policies:view');
        });
    });

    // ==================== Emergency Trips Management ====================
    Route::controller(EmergencyTripController::class)->prefix('emergency-trips')->name('emergency.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:emergency:view');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/{uuid}/cancel', 'emergencyCancel')->name('cancel')->middleware('permission:emergency:cancel');
            Route::post('/{uuid}/reschedule', 'emergencyReschedule')->name('reschedule')->middleware('permission:emergency:reschedule');
        });
    });

    // ==================== Reports & BI ====================
    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/monthly-revenue', 'monthlyRevenue')->name('monthly')->middleware('permission:reports:finance');
            Route::get('/trip-performance', 'tripPerformance')->name('performance')->middleware('permission:reports:performance');
            Route::get('/audit-logs', 'auditLogs')->name('audit-logs')->middleware('permission:reports:audit');
            Route::get('/financial', 'financialReports')->name('financial')->middleware('permission:reports:finance');
            Route::get('/financial/export', 'exportFinancials')->name('export')->middleware('permission:reports:export');
        });
    });

    // ==================== Login Activities ====================
    Route::controller(LoginActivityController::class)->prefix('login-activities')->name('login-activities.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:login-activities:view');
            Route::get('/{loginActivity:uuid}', 'show')->name('show')->middleware('permission:login-activities:view');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/export', 'export')->name('export')->middleware('permission:login-activities:export');
            Route::post('/cleanup', 'cleanup')->name('cleanup')->middleware('permission:login-activities:cleanup');
        });
    });

    // ==================== Commissions Management ====================
    Route::controller(CommissionController::class)->prefix('commissions')->name('commissions.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:commissions:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:commissions:create');
            Route::get('/export', 'export')->name('export')->middleware('permission:commissions:view');
            Route::get('/{commission:uuid}', 'show')->name('show')->middleware('permission:commissions:view');
            Route::get('/{commission:uuid}/edit', 'edit')->name('edit')->middleware('permission:commissions:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:commissions:create');
            Route::put('/{commission:uuid}', 'update')->name('update')->middleware('permission:commissions:edit');
            Route::delete('/{commission:uuid}', 'destroy')->name('destroy')->middleware('permission:commissions:delete');
        });
    });

    // ==================== Provider Settlements ====================
    Route::controller(ProviderSettlementController::class)->prefix('provider-settlements')->name('provider-settlements.')->group(function () {
            Route::middleware('throttle:30,1')->group(function () {
                Route::get('/', 'index')->name('index')->middleware('permission:providers:settle');
                Route::get('/history', 'history')->name('history')->middleware('permission:providers:settle');
            });
            
            Route::middleware('throttle:10,1')->group(function () {
                Route::post('/{providerId}/settle', 'settle')->name('settle')->middleware('permission:providers:settle');
            });
        });

     // ==================== Security Incidents ====================   
    Route::get('/security-incidents', [SecurityIncidentController::class, 'index'])
    ->name('security-incidents')
    ->middleware('permission:security:view');

    Route::post('/unblock', [SecurityIncidentController::class, 'unblock'])
    ->name('security.unblock')
    ->middleware('permission:security:manage');

    Route::post('/block', [SecurityIncidentController::class, 'block'])
    ->name('security.block')
    ->middleware('permission:security:manage');
});