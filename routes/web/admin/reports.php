<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReportController;
/*
Route::prefix('reports')->name('reports.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/financial', [ReportController::class, 'financialReports'])
            ->name('financial')
            ->middleware('permission:reports:financial');
        
        Route::get('/audit-logs', [ReportController::class, 'auditLogs'])
            ->name('audit-logs')
            ->middleware('permission:reports:audit');
    });
});
*/