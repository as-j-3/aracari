<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountStatementController;
use App\Http\Controllers\Admin\BalanceSheetController;
use App\Http\Controllers\Admin\IncomeStatementController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\TrialBalanceController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified'])
    ->prefix('accounting')
    ->name('accounting.')
    ->group(function () {

    Route::controller(AccountController::class)->prefix('accounts')->name('accounts.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:accounts:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:accounts:create');
            Route::get('/{account:uuid}/edit', 'edit')->name('edit')->middleware('permission:accounts:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:accounts:create');
            Route::put('/{account:uuid}', 'update')->name('update')->middleware('permission:accounts:edit');
            Route::delete('/{account:uuid}', 'destroy')->name('destroy')->middleware('permission:accounts:delete');
            Route::post('/{account:uuid}/toggle-status', 'toggleStatus')->name('toggle-status')->middleware('permission:accounts:toggle-status');
        });
    });

    Route::controller(JournalEntryController::class)->prefix('journal-entries')->name('journal-entries.')->group(function () {
        
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:journal:view');
            Route::get('/create', 'create')->name('create')->middleware('permission:journal:create');
            Route::get('/{reference}/edit', 'edit')->name('edit')->middleware('permission:journal:edit');
        });
        
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/', 'store')->name('store')->middleware('permission:journal:create');
            Route::match(['PUT', 'post'], '/{reference}', 'update')->name('update')->middleware('permission:journal:edit');
            Route::delete('/{reference}', 'destroy')->name('destroy')->middleware('permission:journal:delete');
            Route::post('/{reference}/confirm', 'confirm')->name('confirm')->middleware('permission:journal:edit');
            Route::post('/{reference}/reverse', 'reverse')->name('reverse')->middleware('permission:journal:edit');
            Route::post('/{reference}/cancel', 'cancel')->name('cancel')->middleware('permission:journal:edit');
        });
    });

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('reports/trial-balance', TrialBalanceController::class)
            ->middleware('permission:reports:trial-balance')
            ->name('reports.trial-balance');
        
        Route::get('reports/income-statement', IncomeStatementController::class)
            ->middleware('permission:reports:income-statement')
            ->name('reports.income-statement');
        
        Route::get('reports/balance-sheet', BalanceSheetController::class)
            ->middleware('permission:reports:balance-sheet')
            ->name('reports.balance-sheet');
        
        Route::get('reports/account-statement', [AccountStatementController::class, 'create'])
            ->middleware('permission:reports:account-statement')
            ->name('reports.account-statement.form');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('reports/account-statement', [AccountStatementController::class, 'store'])
            ->middleware('permission:reports:account-statement')
            ->name('reports.account-statement');
    });
});