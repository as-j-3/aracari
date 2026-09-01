<?php

use App\Http\Controllers\Admin\ProviderBranchController;
use Illuminate\Support\Facades\Route;

Route::prefix('provider-branches')->name('provider-branches.')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [ProviderBranchController::class, 'index'])->name('index')->middleware('permission:provider-branches:view');
        Route::get('/{uuid}', [ProviderBranchController::class, 'show'])->name('show')->middleware('permission:provider-branches:view');
    });
});