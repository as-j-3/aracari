<?php

namespace App\Services;

use App\Models\Ad;
use App\Repositories\Contracts\AdRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdService
{
    public function __construct(
        protected AdRepositoryInterface $adRepository,
        protected FileUploadService $fileUploadService,
        protected AuditService $auditService
    ) {}

    public function getAllWithStats(int $perPage, ?string $search, ?string $status): array
    {
        $ads = $this->adRepository->getAllAds($perPage, $search, $status);
        $stats = $this->adRepository->getStatistics();

        return [
            'ads' => $ads,
            'stats' => $stats,
        ];
    }

    public function createAd(array $data, $image = null): Ad
    {
        DB::beginTransaction();
        try {
            // Upload image FIRST to get the URL before creating the ad
            // This avoids NOT NULL constraint violation on image_url column
            $imageUrl = null;
            if ($image) {
                $uploadResult = $this->fileUploadService->uploadImage($image, 'ads/temp');
                if (!$uploadResult['success']) {
                    throw new \Exception('فشل رفع الصورة: ' . ($uploadResult['message'] ?? 'خطأ غير معروف'));
                }
                $imageUrl = $uploadResult['file_path'];
            }

            $ad = $this->adRepository->create([
                'user_id' => auth()->id(),
                'title' => $data['title'],
                'description' => $data['description'],
                'image_url' => $imageUrl,
                'target_url' => $data['target_url'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            // Move file from temp to permanent directory
            if ($imageUrl && $ad->id) {
                $permanentPath = 'ads/' . $ad->id . '/' . basename($imageUrl);
                try {
                    \Storage::disk('public')->move($imageUrl, $permanentPath);
                    $this->adRepository->update($ad, ['image_url' => $permanentPath]);
                } catch (\Exception $moveEx) {
                    // If move fails, keep the temp path — file is still accessible
                    Log::warning('Ad image move failed, keeping temp path: ' . $moveEx->getMessage());
                }
            }

            $adId = $ad->id;
            $adData = $ad->fresh()->toArray();
            DB::afterCommit(function () use ($adId, $adData) {
                $this->auditService->log(auth()->id(), 'create', 'ads', $adId, null, $adData);
            });

            DB::commit();
            return $ad->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdService::createAd failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateAd(Ad $ad, array $data, $image = null): Ad
    {
        DB::beginTransaction();
        try {
            $oldData = $ad->toArray();
            $oldImageUrl = $ad->image_url;

            if ($image) {
                $uploadResult = $this->fileUploadService->uploadImage($image, 'ads/' . $ad->id);
                if (!$uploadResult['success']) {
                    DB::rollBack();
                    throw new \Exception($uploadResult['message']);
                }
                $data['image_url'] = $uploadResult['file_path'];
            }

            $this->adRepository->update($ad, $data);
            $newData = $ad->fresh()->toArray();

            $finalOldImageUrl = $oldImageUrl;
            DB::afterCommit(function () use ($finalOldImageUrl, $oldData, $newData) {
                if ($finalOldImageUrl) {
                    $this->fileUploadService->deleteFile($finalOldImageUrl);
                }
                $this->auditService->log(auth()->id(), 'update', 'ads', $newData['id'], $oldData, $newData);
            });

            DB::commit();
            return $ad->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdService::updateAd failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteAd(Ad $ad): void
    {
        DB::beginTransaction();
        try {
            $oldData = $ad->toArray();
            $imageUrl = $ad->image_url;
            $adId = $ad->id;

            $this->adRepository->delete($ad);

            DB::afterCommit(function () use ($imageUrl, $adId, $oldData) {
                if ($imageUrl) {
                    $this->fileUploadService->deleteFile($imageUrl);
                }
                $this->auditService->log(auth()->id(), 'delete', 'ads', $adId, $oldData, null);
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdService::deleteAd failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
