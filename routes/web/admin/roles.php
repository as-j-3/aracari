<?php

use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->name('roles.')->group(function () {
    
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles:view')->name('index');
        Route::get('/create', [RoleController::class, 'create'])->middleware('permission:roles:create')->name('create');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles:edit')->name('edit');
        Route::get('/{role}/permissions', [RoleController::class, 'permissions'])->middleware('permission:permissions:assign')->name('permissions');
    });
    
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles:create')->name('store');
        Route::match(['PUT', 'post'], '/{role}', [RoleController::class, 'update'])->middleware('permission:roles:edit')->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles:delete')->name('destroy');
        Route::post('/{role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:permissions:assign')->name('assign-permissions');
    });
});