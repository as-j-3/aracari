<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\FileUploadController;

Route::prefix('files')->middleware(['web', 'auth', 'verified', 'throttle:10,1'])->group(function () {
    Route::post('/provider/upload', [FileUploadController::class, 'uploadProviderFile'])
        ->middleware('permission:provider_files:upload');
    Route::post('/vehicle/{vehicleId:uuid}/photos', [FileUploadController::class, 'uploadVehiclePhotos'])
        ->middleware('permission:vehicles:edit');
    Route::post('/passenger/{passengerId:uuid}/photos', [FileUploadController::class, 'uploadPassengerPhotos'])
        ->middleware('permission:passengers:edit');
    Route::post('/provider/{providerId:uuid}/brand-photo', [FileUploadController::class, 'uploadProviderBrandPhoto'])
        ->middleware('permission:providers:edit');
    Route::post('/ad/{adId:uuid}/image', [FileUploadController::class, 'uploadAdImage'])
        ->middleware('permission:ads:edit');
    Route::delete('/delete', [FileUploadController::class, 'deleteFile'])
        ->middleware('permission:files:delete');
});