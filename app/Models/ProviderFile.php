<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\FileUploadService;

class ProviderFile extends Model
{
    use BelongsToProvider, HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'name',
        'file',
        'file_type',
        'status',
        'note',
    ];

    protected $casts = [
        'file_type' => 'string',
        'status' => 'string',
    ];

    /**
     * Get the provider service for the provider file.
     */
    public function provider()
    {
        return $this->belongsTo(ProviderService::class);
    }

    /**
     * جلب رابط الملف
     */
    public function getFileUrlAttribute(): string
    {
        return (new FileUploadService())->getFileUrl($this->file);
    }

    /**
     * التحقق من وجود الملف
     */
    public function fileExists(): bool
    {
        return (new FileUploadService())->fileExists($this->file);
    }

    /**
     * حذف الملف عند حذف السجل
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($providerFile) {
            // Delete file from storage on both soft and force delete
            $fileUploadService = new FileUploadService();
            $fileUploadService->deleteFile($providerFile->file);
        });

        static::restoring(function ($providerFile) {
            // Note: The file cannot be restored as it was deleted from storage
            // Consider logging a warning or implementing a backup strategy
            \Illuminate\Support\Facades\Log::warning('Attempting to restore ProviderFile but file was deleted from storage', [
                'provider_file_id' => $providerFile->id,
                'file_path' => $providerFile->file,
            ]);
        });
    }
}
