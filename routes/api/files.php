<?php

use App\Http\Controllers\Api\FileAccessController;
use App\Http\Controllers\Api\FileUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/user-upload', [FileUploadController::class, 'userUpload'])
            ->name('api.files.user.upload');
        
        Route::post('/upload', [FileUploadController::class, 'uploadTemp'])
            ->name('api.files.temp.upload');
    });
    
    Route::get('/user-view/{path}', [FileAccessController::class, 'userView'])
        ->where('path', '.*')
        ->name('api.files.user.view');
});

Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::get('/secure-view/{path}', [FileAccessController::class, 'secureView'])
        ->where('path', '.*')
        ->middleware('permission:files:view')
        ->name('api.files.secure.view');
    
    Route::post('/admin-upload', [FileUploadController::class, 'upload'])
        ->middleware(['permission:files:upload', 'throttle:5,1'])
        ->name('api.files.admin.upload');
});