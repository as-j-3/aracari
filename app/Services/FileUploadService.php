<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    protected array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected array $allowedDocumentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    protected int $maxFileSize = 10240; // 10MB in KB
    protected string $defaultDisk = 'public';
    protected array $secureDirectories = ['providers', 'passengers', 'temp','vehicles','ads','cities','trips'];

    public function uploadFile(UploadedFile $file, string $directory, string $type = 'document'): array
    {
        if (!$this->isValidFileType($file, $type)) {
            return [
                'success' => false,
                'message' => 'نوع الملف غير مسموح به',
            ];
        }

        if (!$this->isValidFileSize($file)) {
            return [
                'success' => false,
                'message' => 'حجم الملف كبير جدًا',
            ];
        }

        if (!$this->isFileContentSafe($file)) {
            return [
                'success' => false,
                'message' => 'محتوى الملف غير آمن',
            ];
        }

        try {
            $processedFile = $this->processFile($file, $type);
            $fileName = $this->generateUniqueFileName($processedFile, $type);
            $disk = $this->getDiskForDirectory($directory);
            $path = $processedFile->storeAs($directory, $fileName, $disk);

            return [
                'success' => true,
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => $processedFile->getSize(),
                'file_type' => strtolower($processedFile->getClientOriginalExtension()),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'فشل في رفع الملف: ' . $e->getMessage(),
            ];
        }
    }

    public function uploadImage(UploadedFile $file, string $directory, array $options = []): array
    {
        $result = $this->uploadFile($file, $directory, 'image');

        if (!$result['success']) {
            return $result;
        }

        $this->processImage($result['file_path'], $options);

        return $result;
    }

    protected function isValidFileType(UploadedFile $file, string $type): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($type === 'image') {
            return in_array($extension, $this->allowedImageTypes, true);
        }

        if ($type === 'document') {
            return in_array($extension, $this->allowedDocumentTypes, true);
        }

        // ✅ Fail-closed: لا نقبل أي نوع غير معروف (كان يقبل أي شيء)
        Log::warning('File upload rejected: unknown type', [
            'type' => $type,
            'extension' => $extension,
            'filename' => $file->getClientOriginalName(),
        ]);
        return false;
    }

    protected function isValidFileSize(UploadedFile $file): bool
    {
        return $file->getSize() <= ($this->maxFileSize * 1024);
    }

    protected function processFile(UploadedFile $file, string $type): UploadedFile
    {
        if ($type === 'image') {
            return $this->processImageFile($file);
        }

        return $file;
    }

    protected function processImageFile(UploadedFile $file): UploadedFile
    {
        // Security: Re-encode images to prevent polyglot attacks
        // Polyglot files can embed malicious code (PHP, JS) in EXIF metadata
        // Re-encoding strips all metadata and ensures file is valid image only
        
        try {
            // Check if GD or Imagick extension is available
            if (!extension_loaded('gd') && !extension_loaded('imagick')) {
                Log::warning('No image processing library available, skipping re-encoding', [
                    'file' => $file->getClientOriginalName(),
                ]);
                return $file;
            }

            $tempPath = sys_get_temp_dir() . '/' . uniqid('img_', true) . '.jpg';
            $mimeType = $file->getMimeType();
            
            // Create image from uploaded file based on MIME type
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $sourceImage = imagecreatefromjpeg($file->getPathname());
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($file->getPathname());
                    // Preserve transparency for PNG
                    imagealphablending($sourceImage, true);
                    imagesavealpha($sourceImage, true);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($file->getPathname());
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $sourceImage = imagecreatefromwebp($file->getPathname());
                    } else {
                        Log::warning('WebP not supported, keeping original', ['file' => $file->getClientOriginalName()]);
                        return $file;
                    }
                    break;
                default:
                    Log::warning('Unsupported image type for re-encoding: ' . $mimeType);
                    return $file;
            }

            if (!$sourceImage) {
                Log::error('Failed to create image from uploaded file', [
                    'file' => $file->getClientOriginalName(),
                    'mime' => $mimeType,
                ]);
                return $file;
            }

            // Re-encode as JPEG (strip all metadata, remove any embedded code)
            // Quality 85 provides good balance between size and quality
            if (!imagejpeg($sourceImage, $tempPath, 85)) {
                Log::error('Failed to re-encode image', ['file' => $file->getClientOriginalName()]);
                imagedestroy($sourceImage);
                return $file;
            }

            // Clean up source image from memory
            imagedestroy($sourceImage);

            // Create new UploadedFile from re-encoded image
            $newFile = new UploadedFile(
                $tempPath,
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg',
                'image/jpeg',
                null,
                true // Mark as test file to avoid move issues
            );

            Log::info('Image re-encoded successfully', [
                'original' => $file->getClientOriginalName(),
                'size_before' => $file->getSize(),
                'size_after' => filesize($tempPath),
            ]);

            return $newFile;

        } catch (\Exception $e) {
            Log::error('Image re-encoding failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
            ]);
            // Fail safe: return original file if re-encoding fails
            // But log the error for security monitoring
            return $file;
        }
    }

    protected function processImage(string $filePath, array $options): void
    {
        // No-op by design. Resizing/cropping is skipped when image libraries
        // are unavailable in the runtime environment.
    }

    protected function generateUniqueFileName(UploadedFile $file, string $type): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$type}_{$timestamp}_{$random}.{$extension}";
    }

    public function deleteFile(string $filePath): bool
    {
        try {
            $disk = $this->getDiskForDirectory($filePath);
            return Storage::disk($disk)->delete($filePath);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFileUrl(string $filePath): string
    {
        $disk = $this->getDiskForDirectory($filePath);

        if ($disk === 'local') {
            return route('api.files.user.view', ['path' => $filePath]);
        }

        return Storage::disk('public')->url($filePath);
    }

    public function fileExists(string $filePath): bool
    {
        $disk = $this->getDiskForDirectory($filePath);
        return Storage::disk($disk)->exists($filePath);
    }

    protected function getDiskForDirectory(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === 'passengers' || str_starts_with($path, 'passengers/')) {
            return 'local';
        }
        
        // providers logos are public-facing (shown in trip listings, etc.) → use public disk
        return 'public';
    }

    public function setAllowedTypes(array $imageTypes, array $documentTypes): void
    {
        $this->allowedImageTypes = $imageTypes;
        $this->allowedDocumentTypes = $documentTypes;
    }

    public function setMaxFileSize(int $sizeInKB): void
    {
        $this->maxFileSize = $sizeInKB;
    }

    protected function isFileContentSafe(UploadedFile $file): bool
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMimeType = $finfo->file($file->getPathName());

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];

        if (!in_array($actualMimeType, $allowedMimeTypes, true)) {
            return false;
        }

        if ($file->getSize() > ($this->maxFileSize * 1024)) {
            return false;
        }
        return true;
    }
    /**
     * Check if user has permission to upload file for a provider
     *
     * @param string $providerId Provider UUID
     * @return bool True if user has permission
     */
    public function canUploadProviderFile(string $providerId): bool
    {
        $provider = \App\Models\ProviderService::where('uuid', $providerId)->first();

        if (!$provider) {
            return false;
        }

        return $provider->user_id === auth()->id() || auth()->user()?->isAdmin();
    }

    /**
     * Check if user has permission to upload file for a vehicle
     *
     * @param string $vehicleId Vehicle UUID
     * @return bool True if user has permission
     */
    public function canUploadVehicleFile(string $vehicleId): bool
    {
        $vehicle = \App\Models\Vehicle::where('uuid', $vehicleId)->first();

        if (!$vehicle) {
            return false;
        }

        return $vehicle->user_id === auth()->id() || auth()->user()?->isAdmin();
    }

    /**
     * Check if user has permission to upload file for a passenger
     *
     * @param string $passengerId Passenger UUID
     * @return bool True if user has permission
     */
    public function canUploadPassengerFile(string $passengerId): bool
    {
        $passenger = \App\Models\Passenger::where('uuid', $passengerId)->first();

        if (!$passenger) {
            return false;
        }

        // Use created_by instead of user_id
        return $passenger->created_by === auth()->id() || auth()->user()?->isAdmin();
    }

    /**
     * Check if user has permission to upload file for an ad
     *
     * @param string $adId Ad UUID
     * @return bool True if user has permission
     */
    public function canUploadAdFile(string $adId): bool
    {
        $ad = \App\Models\Ad::where('uuid', $adId)->first();

        if (!$ad) {
            return false;
        }

        return $ad->user_id === auth()->id() || auth()->user()?->isAdmin();
    }

    /**
     * Check if user has permission to delete a file
     *
     * @param string $filePath File path to check
     * @param \App\Models\User|null $user User to check permission for
     * @return bool True if user has permission to delete the file
     */
    public function canDeleteFile(string $filePath, $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }
        if (str_contains($filePath, '..') || str_contains($filePath, "\0")) {
            return false;
        }
        // Admin can delete any file
        if ($user->isAdmin()) {
            return true;
        }

        // Extract entity information from file path
        // Path patterns: providers/{id}/..., vehicles/{id}/..., passengers/{id}/..., ads/{id}/...
        $pathParts = explode('/', trim($filePath, '/'));

        if (empty($pathParts)) {
            return false;
        }

        $entityType = $pathParts[0];
        $entityId = $pathParts[1] ?? null;

        switch ($entityType) {
            case 'providers':
                // Check if user owns this provider
                $provider = \App\Models\ProviderService::find($entityId);
                return $provider && $provider->user_id === $user->id;

            case 'vehicles':
                // Check if user owns this vehicle
                $vehicle = \App\Models\Vehicle::find($entityId);
                return $vehicle && $vehicle->user_id === $user->id;

            case 'passengers':
                // Check if user created this passenger
                $passenger = \App\Models\Passenger::find($entityId);
                return $passenger && $passenger->created_by === $user->id;

            case 'ads':
                // Check if user owns this ad
                $ad = \App\Models\Ad::find($entityId);
                return $ad && $ad->user_id === $user->id;

            default:
                return false;
        }
    }
}
