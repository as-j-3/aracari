<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PersistAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 3;
    public $backoff = [30, 60, 120];
    public $timeout = 60;

    public function __construct(
        public readonly ?int $performerId,
        public readonly string $action,
        public readonly string $tableName,
        public readonly int $recordId,
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
        public readonly ?string $timestamp = null,
    ) {
    }

    public function handle(): void
    {
    try {
        $data = [
            'user_id' => $this->performerId,
            'action' => $this->translateAction($this->action),
            'table_name' => $this->translateTable($this->tableName),
            'record_id' => $this->recordId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ];

        if ($this->timestamp) {
            $data['created_at'] = $this->timestamp;
            $data['updated_at'] = $this->timestamp;
        }

        AuditLog::create($data);
        
    } catch (\Throwable $exception) {
        Log::error('Failed to persist audit log: ' . $exception->getMessage(), [
            'performer_id' => $this->performerId,
            'action' => $this->action,
            'table' => $this->tableName,
            'record_id' => $this->recordId,
            'exception' => $exception->getMessage(),
        ]);

        throw $exception;
    }
}

    public function failed(\Throwable $exception): void
    {
        Log::critical('Audit log job failed permanently after all retries', [
            'performer_id' => $this->performerId,
            'action' => $this->action,
            'table' => $this->tableName,
            'record_id' => $this->recordId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

    }

    private function translateAction(string $action): string
    {
        return match ($action) {
            'created' => 'إنشاء',
            'updated' => 'تعديل',
            'deleted' => 'حذف',
            'restored' => 'استعادة',
            'confirmed' => 'اعتماد',
            'reversed' => 'عكس',
            'cancelled' => 'إلغاء',
            'settlement' => 'تسوية',
            'toggle-status' => 'تغيير الحالة',
            default => $action,
        };
    }

    private function translateTable(string $table): string
    {
        return match ($table) {
            'users' => 'المستخدمين',
            'bookings' => 'الحجوزات',
            'trips' => 'الرحلات',
            'vehicles' => 'المركبات',
            'provider_services' => 'خدمات المزودين',
            'provider_files' => 'ملفات المزودين',
            'roles' => 'الأدوار',
            'journal_entries' => 'القيود المحاسبية',
            'passengers' => 'المسافرين',
            'cities' => 'المدن',
            default => $table,
        };
    }
}
