<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FileUpload\UploadProviderFileRequest;
use App\Http\Requests\Web\FileUpload\UploadVehiclePhotosRequest;
use App\Http\Requests\Web\FileUpload\UploadPassengerPhotosRequest;
use App\Http\Requests\Web\FileUpload\UploadProviderBrandPhotoRequest;
use App\Http\Requests\Web\FileUpload\UploadAdImageRequest;
use App\Http\Requests\Web\FileUpload\DeleteFileRequest;
use App\Services\FileUploadService;
use App\Services\AuditService;
use App\Models\ProviderFile;
use App\Models\Vehicle;
use App\Models\Passenger;
use App\Models\ProviderService;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    protected FileUploadService $fileUploadService;
    protected AuditService $auditService;

    public function __construct(FileUploadService $fileUploadService, AuditService $auditService)
    {
        $this->fileUploadService = $fileUploadService;
        $this->auditService = $auditService;
    }


    public function uploadProviderFile(UploadProviderFileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $providerId = $validated['provider_id'];

        if (!$this->fileUploadService->canUploadProviderFile($providerId)) {
            return $this->error('غير مصرح لك برفع ملفات لهذا المزود', 403);
        }

        $file = $request->file('file');
        $fileType = $validated['file_type'] ?? 'document';
        $uploadedPath = null;

        try {
            $uploadResult = $this->fileUploadService->uploadFile(
                $file,
                "providers/{$providerId}/docs",
                $fileType
            );

            if (!$uploadResult['success']) {
                return $this->error($uploadResult['message'], 400);
            }

            $uploadedPath = $uploadResult['file_path'];

            DB::beginTransaction();

            $providerFile = ProviderFile::create([
                'provider_id' => $providerId,
                'name'        => $validated['name'],
                'file'        => $uploadedPath,
                'file_type'   => $fileType,
                'note'        => $validated['note'] ?? null,
                'status'      => 'active',
            ]);

            DB::afterCommit(function () use ($providerFile) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'provider_files',
                    $providerFile->id,
                    null,
                    $providerFile->fresh()->toArray()
                );
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف بنجاح',
                'file'    => [
                    'id'        => $providerFile->id,
                    'name'      => $providerFile->name,
                    'file_url'  => $providerFile->file_url,
                    'file_type' => $providerFile->file_type,
                    'file_size' => $uploadResult['file_size'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($uploadedPath) {
                $this->fileUploadService->deleteFile($uploadedPath);
            }

            Log::error('Provider file upload failed', [
                'error'       => $e->getMessage(),
                'provider_id' => $providerId,
                'trace'       => $e->getTraceAsString(),
            ]);

            return $this->error('فشل رفع الملف. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function uploadVehiclePhotos(UploadVehiclePhotosRequest $request, string $vehicleId): JsonResponse
    {
        $vehicle = Vehicle::where('uuid', $vehicleId)->firstOrFail();

        if (!$this->fileUploadService->canUploadVehicleFile($vehicle->id)) {
            return $this->error('غير مصرح لك', 403);
        }

        $updates = [];
        $oldPaths = [];
        $newPaths = [];

        try {
            if ($request->hasFile('outside_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('outside_photo'),
                    "vehicles/{$vehicleId}/photos",
                    ['width' => 1200, 'height' => 800]
                );
                if ($result['success']) {
                    if ($vehicle->outside_photo) {
                        $oldPaths[] = $vehicle->outside_photo;
                    }
                    $updates['outside_photo'] = $result['file_path'];
                    $newPaths[] = $result['file_path'];
                }
            }

            if ($request->hasFile('inside_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('inside_photo'),
                    "vehicles/{$vehicleId}/photos",
                    ['width' => 1200, 'height' => 800]
                );
                if ($result['success']) {
                    if ($vehicle->inside_photo) {
                        $oldPaths[] = $vehicle->inside_photo;
                    }
                    $updates['inside_photo'] = $result['file_path'];
                    $newPaths[] = $result['file_path'];
                }
            }

            if (empty($updates)) {
                return $this->error('لم يتم رفع أي صورة', 400);
            }

            DB::beginTransaction();

            $oldValues = $vehicle->toArray();
            $vehicle->update($updates);

            DB::afterCommit(function () use ($vehicle, $oldValues, $oldPaths) {
                foreach ($oldPaths as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'vehicles',
                    $vehicle->id,
                    $oldValues,
                    $vehicle->fresh()->toArray()
                );
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الصور بنجاح',
                'photos'  => [
                    'outside_photo_url' => $vehicle->outside_photo
                        ? $this->fileUploadService->getFileUrl($vehicle->outside_photo)
                        : null,
                    'inside_photo_url' => $vehicle->inside_photo
                        ? $this->fileUploadService->getFileUrl($vehicle->inside_photo)
                        : null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($newPaths as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            Log::error('Vehicle photos upload failed', [
                'error'     => $e->getMessage(),
                'vehicle'   => $vehicleId,
                'trace'     => $e->getTraceAsString(),
            ]);

            return $this->error('فشل رفع الصور. يرجى المحاولة مرة أخرى.', 500);
        }
    }


    public function uploadPassengerPhotos(UploadPassengerPhotosRequest $request, string $passengerId): JsonResponse
    {
        $passenger = Passenger::where('uuid', $passengerId)->firstOrFail();

        if (!$this->fileUploadService->canUploadPassengerFile($passenger->id)) {
            return $this->error('غير مصرح لك', 403);
        }

        $updates = [];
        $oldPaths = [];
        $newPaths = [];

        try {
            if ($request->hasFile('personal_card_id_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('personal_card_id_photo'),
                    "passengers/{$passengerId}/photos",
                    ['width' => 800, 'height' => 600]
                );
                if ($result['success']) {
                    if ($passenger->personal_card_id_photo) {
                        $oldPaths[] = $passenger->personal_card_id_photo;
                    }
                    $updates['personal_card_id_photo'] = $result['file_path'];
                    $newPaths[] = $result['file_path'];
                }
            }

            if ($request->hasFile('passport_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('passport_photo'),
                    "passengers/{$passengerId}/photos",
                    ['width' => 800, 'height' => 600]
                );
                if ($result['success']) {
                    if ($passenger->passport_photo) {
                        $oldPaths[] = $passenger->passport_photo;
                    }
                    $updates['passport_photo'] = $result['file_path'];
                    $newPaths[] = $result['file_path'];
                }
            }

            if (empty($updates)) {
                return $this->error('لم يتم رفع أي صورة', 400);
            }

            DB::beginTransaction();

            $oldValues = $passenger->toArray();
            $passenger->update($updates);

            DB::afterCommit(function () use ($passenger, $oldValues, $oldPaths) {
                foreach ($oldPaths as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'passengers',
                    $passenger->id,
                    $oldValues,
                    $passenger->fresh()->toArray()
                );
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الصور بنجاح',
                'photos'  => [
                    'personal_card_id_photo_url' => $passenger->personal_card_id_photo
                        ? $this->fileUploadService->getFileUrl($passenger->personal_card_id_photo)
                        : null,
                    'passport_photo_url' => $passenger->passport_photo
                        ? $this->fileUploadService->getFileUrl($passenger->passport_photo)
                        : null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($newPaths as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            Log::error('Passenger photos upload failed', [
                'error'     => $e->getMessage(),
                'passenger' => $passengerId,
            ]);

            return $this->error('فشل رفع الصور. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function uploadProviderBrandPhoto(UploadProviderBrandPhotoRequest $request, string $providerId): JsonResponse
    {
        $provider = ProviderService::where('uuid', $providerId)->firstOrFail();

        if (!$this->fileUploadService->canUploadProviderFile($provider->id)) {
            return $this->error('غير مصرح لك', 403);
        }

        $oldBrandPhoto = $provider->brand_photo;
        $newPath = null;

        try {
            $uploadResult = $this->fileUploadService->uploadImage(
                $request->file('brand_photo'),
                "providers/{$providerId}/brand",
                ['width' => 400, 'height' => 400]
            );

            if (!$uploadResult['success']) {
                return $this->error($uploadResult['message'], 400);
            }

            $newPath = $uploadResult['file_path'];

            DB::beginTransaction();

            $provider->update(['brand_photo' => $newPath]);

            DB::afterCommit(function () use ($provider, $newPath, $oldBrandPhoto) {
                if ($oldBrandPhoto) {
                    $this->fileUploadService->deleteFile($oldBrandPhoto);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'provider_services',
                    $provider->id,
                    ['brand_photo' => $oldBrandPhoto],
                    ['brand_photo' => $newPath]
                );
            });

            DB::commit();

            return response()->json([
                'success'         => true,
                'message'         => 'تم رفع الشعار بنجاح',
                'brand_photo_url' => $this->fileUploadService->getFileUrl($newPath),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($newPath) {
                $this->fileUploadService->deleteFile($newPath);
            }

            Log::error('Provider brand photo upload failed', [
                'error'   => $e->getMessage(),
                'provider'=> $providerId,
            ]);

            return $this->error('فشل رفع الشعار. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function uploadAdImage(UploadAdImageRequest $request, string $adId): JsonResponse
    {
        $ad = Ad::where('uuid', $adId)->firstOrFail();

        if (!$this->fileUploadService->canUploadAdFile($ad->id)) {
            return $this->error('غير مصرح لك', 403);
        }

        $oldImage = $ad->image_url;
        $newPath = null;

        try {
            $uploadResult = $this->fileUploadService->uploadImage(
                $request->file('image'),
                "ads/{$adId}/images",
                ['width' => 1200, 'height' => 630]
            );

            if (!$uploadResult['success']) {
                return $this->error($uploadResult['message'], 400);
            }

            $newPath = $uploadResult['file_path'];

            DB::beginTransaction();

            $ad->update(['image_url' => $newPath]);

            DB::afterCommit(function () use ($ad, $newPath, $oldImage) {
                if ($oldImage) {
                    $this->fileUploadService->deleteFile($oldImage);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'ads',
                    $ad->id,
                    ['image_url' => $oldImage],
                    ['image_url' => $newPath]
                );
            });

            DB::commit();

            return response()->json([
                'success'   => true,
            'message'   => 'تم رفع صورة الإعلان بنجاح',
                'image_url' => $this->fileUploadService->getFileUrl($newPath),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($newPath) {
                $this->fileUploadService->deleteFile($newPath);
            }

            Log::error('Ad image upload failed', [
                'error' => $e->getMessage(),
                'ad'    => $adId,
            ]);

            return $this->error('فشل رفع صورة الإعلان. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function deleteFile(DeleteFileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filePath = $validated['file_path'];

        if (!$this->fileUploadService->canDeleteFile($filePath)) {
            return $this->error('غير مصرح لك بحذف هذا الملف', 403);
        }

        try {
            $deleted = $this->fileUploadService->deleteFile($filePath);

            if (!$deleted) {
                return $this->error('فشل في حذف الملف', 400);
            }

            $this->auditService->log(
                auth()->id(),
                'delete',
                $validated['entity_type'] . '_files',
                $validated['entity_id'],  
                ['file_path' => $filePath],
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملف بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('File delete failed', [
                'error'     => $e->getMessage(),
                'file_path' => $filePath,
            ]);
            return $this->error('فشل حذف الملف.', 500);
        }
    }
}