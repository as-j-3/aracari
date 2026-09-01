<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class FileValidationService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'image/webp',
    ];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function validateImageFile(UploadedFile $file, string $fieldName = 'file'): array
    {
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'max:' . (self::MAX_FILE_SIZE / 1024),
                'mimes:' . implode(',', ['jpeg', 'png', 'jpg', 'webp']),
                'mimetypes:' . implode(',', self::ALLOWED_MIME_TYPES),
            ],
        ], [
            'file.required' => 'الملف مطلوب',
            'file.file' => 'يجب أن يكون ملفًا صالحًا',
            'file.max' => 'حجم الملف يجب أن لا يتجاوز 5 ميجابايت',
            'file.mimes' => 'نوع الملف غير مدعوم. المسموح: JPEG, PNG, WebP',
            'file.mimetypes' => 'نوع الملف غير مدعوم',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        $securityCheck = $this->performSecurityCheck($file);
        if (!$securityCheck['valid']) {
            return $securityCheck;
        }

        $imageCheck = $this->validateImageContent($file);
        if (!$imageCheck['valid']) {
            return $imageCheck;
        }

        return ['valid' => true];
    }

    private function performSecurityCheck(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'valid' => false,
                'errors' => ['امتداد الملف غير مدعوم'],
            ];
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return [
                'valid' => false,
                'errors' => ['نوع الملف غير مدعوم'],
            ];
        }

        $content = $file->get();
        $dangerousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return [
                    'valid' => false,
                    'errors' => ['الملف يحتوي على محتوى خبيث'],
                ];
            }
        }

        $signatureCheck = $this->validateFileSignature($content, $extension);
        if (!$signatureCheck['valid']) {
            return $signatureCheck;
        }

        return ['valid' => true];
    }

    private function validateFileSignature(string $content, string $extension): array
    {
        $signatures = [
            'jpg' => "\xFF\xD8\xFF",
            'jpeg' => "\xFF\xD8\xFF",
            'png' => "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A",
            'webp' => 'RIFF',
        ];

        if (!isset($signatures[$extension])) {
            return ['valid' => false, 'errors' => ['امتداد غير معروف']];
        }

        $expectedSignature = $signatures[$extension];
        $actualSignature = substr($content, 0, strlen($expectedSignature));

        if ($actualSignature !== $expectedSignature) {
            return [
                'valid' => false,
                'errors' => ['توقيع الملف غير متطابق'],
            ];
        }

        return ['valid' => true];
    }

    private function validateImageContent(UploadedFile $file): array
    {
        try {
            $dimensions = @getimagesize($file->getRealPath());

            if ($dimensions === false) {
                return [
                    'valid' => false,
                    'errors' => ['الملف ليس صورة صالحة'],
                ];
            }

            $width = $dimensions[0] ?? 0;
            $height = $dimensions[1] ?? 0;

            if ($width < 100 || $height < 100) {
                return [
                    'valid' => false,
                    'errors' => ['أبعاد الصورة صغيرة جدًا. الحد الأدنى: 100x100'],
                ];
            }

            if ($width > 5000 || $height > 5000) {
                return [
                    'valid' => false,
                    'errors' => ['أبعاد الصورة كبيرة جدًا. الحد الأقصى: 5000x5000'],
                ];
            }

            $ratio = $height > 0 ? ($width / $height) : 0;
            if ($ratio > 10 || $ratio < 0.1) {
                return [
                    'valid' => false,
                    'errors' => ['نسبة أبعاد الصورة غير طبيعية'],
                ];
            }
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['الملف ليس صورة صالحة'],
            ];
        }

        return ['valid' => true];
    }

    public function saveImageWithCleanup(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        $fileName = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = $directory . '/' . $fileName;

        Storage::disk('public')->putFileAs($directory, $file, $fileName);

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);

            \Log::info('Old image file deleted', [
                'old_path' => $oldPath,
                'new_path' => $path,
            ]);
        }

        return $path;
    }

    public function cleanupOrphanedFiles(): array
    {
        $cleanedFiles = [];
        $errors = [];

        $passengerPhotos = Storage::disk('public')->allFiles('passengers');
        foreach ($passengerPhotos as $photoPath) {
            if (!$this->isPassengerPhotoLinked($photoPath)) {
                try {
                    Storage::disk('public')->delete($photoPath);
                    $cleanedFiles[] = $photoPath;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete {$photoPath}: " . $e->getMessage();
                }
            }
        }

        $vehiclePhotos = Storage::disk('public')->allFiles('vehicles');
        foreach ($vehiclePhotos as $photoPath) {
            if (!$this->isVehiclePhotoLinked($photoPath)) {
                try {
                    Storage::disk('public')->delete($photoPath);
                    $cleanedFiles[] = $photoPath;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete {$photoPath}: " . $e->getMessage();
                }
            }
        }

        return [
            'cleaned_files' => $cleanedFiles,
            'errors' => $errors,
            'total_cleaned' => count($cleanedFiles),
        ];
    }

    private function isPassengerPhotoLinked(string $photoPath): bool
    {
        if (preg_match('/passengers\/(\d+)\/photos\//', $photoPath, $matches)) {
            $passengerId = $matches[1];
            return \App\Models\Passenger::where('id', $passengerId)
                ->where(function ($query) use ($photoPath) {
                    $query->where('personal_card_id_photo', $photoPath)
                        ->orWhere('passport_photo', $photoPath);
                })
                ->exists();
        }

        return false;
    }

    private function isVehiclePhotoLinked(string $photoPath): bool
    {
        if (preg_match('/vehicles\/(\d+)\/photos\//', $photoPath, $matches)) {
            $vehicleId = $matches[1];
            return \App\Models\Vehicle::where('id', $vehicleId)
                ->where(function ($query) use ($photoPath) {
                    $query->where('brand_photo', $photoPath)
                        ->orWhere('license_photo', $photoPath);
                })
                ->exists();
        }

        return false;
    }
}
