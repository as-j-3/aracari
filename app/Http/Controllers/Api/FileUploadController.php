<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FileUploadController extends Controller
{
    protected FileUploadService $fileUploadService;


    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function userUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx', // 10MB with specific allowed types
            'type' => 'in:image,document',
            'target_type' => 'required|in:passenger,passengers,provider,providers',
            'target_id' => 'required|integer',
        ]);

        $user = $request->user();
        $targetType = $this->normalizeTargetType($request->target_type);
        $targetId = $request->target_id;

        if (!$this->isUserAuthorizedForUpload($user, $targetType, $targetId)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك برفع الملفات لهذا الكائن'
            ], 403);
        }

        $file = $request->file('file');
        $type = $request->type ?? 'image';
        $directory = "{$targetType}/{$targetId}";

        $uploadResult = ($type === 'image') 
            ? $this->fileUploadService->uploadImage($file, $directory)
            : $this->fileUploadService->uploadFile($file, $directory, 'document');

        if (!$uploadResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $uploadResult['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح.',
            'file_path' => $uploadResult['file_path'],
            'file_url'  => $this->fileUploadService->getFileUrl($uploadResult['file_path'])
        ]);
    }

    protected function isUserAuthorizedForUpload($user, string $targetType, int $targetId): bool
    {
        switch ($targetType) {
            case 'passengers':
                $passenger = \App\Models\Passenger::find($targetId);
                return $passenger && $passenger->created_by === $user->id;

            case 'providers':
                $provider = \App\Models\ProviderService::find($targetId);
                return $provider && ($provider->user_id === $user->id || $provider->user_id === $user->user_parent);

            default:
                return false;
        }
    }


    public function upload(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك برفع الملفات كأدمن'
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx', // 10MB with specific allowed types
            'type' => 'in:image,document',
            'directory' => 'nullable|string|regex:/^[a-zA-Z0-9\-_\/]+$/', 
        ]);

        $file = $request->file('file');
        $type = $request->type ?? 'image';
        $directory = $request->directory ?? 'admin';

        $uploadResult = ($type === 'image')
            ? $this->fileUploadService->uploadImage($file, $directory)
            : $this->fileUploadService->uploadFile($file, $directory, 'document');

        if (!$uploadResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $uploadResult['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح.',
            'file_path' => $uploadResult['file_path'],
            'file_url'  => $this->fileUploadService->getFileUrl($uploadResult['file_path'])
        ]);
    }


    public function uploadTemp(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx', // 10MB
            'type' => 'in:image,document',
        ]);

        $file = $request->file('file');
        $type = $request->type ?? 'image';
        $directory = 'temp/' . $request->user()->id;

        $uploadResult = ($type === 'image') 
            ? $this->fileUploadService->uploadImage($file, $directory)
            : $this->fileUploadService->uploadFile($file, $directory, 'document');

        if (!$uploadResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $uploadResult['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف المؤقت بنجاح.',
            'temp_path' => $uploadResult['file_path'], // Mobile developer sends this path as string in BookTrip Request
            'file_url'  => $this->fileUploadService->getFileUrl($uploadResult['file_path'])
        ]);
    }

    protected function normalizeTargetType(string $targetType): string
    {
        return match ($targetType) {
            'passenger' => 'passengers',
            'provider' => 'providers',
            default => $targetType,
        };
    }
}