<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\AuditService;
use Carbon\Carbon;
use App\Models\User;
use App\Constants\UserTypes;
use Exception;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
        {--keep=7 : عدد النسخ الاحتياطية التي يتم الاحتفاظ بها}
        {--path= : مسار مخصص لحفظ النسخة}';

    protected $description = 'إنشاء نسخة احتياطية من قاعدة البيانات';

    private ?AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->auditService = app(AuditService::class);
    }

    public function handle(): int
    {
        $startTime = microtime(true);
        $keep = (int) $this->option('keep');
        $backupPath = $this->option('path') ?? storage_path('app/backups');

        $adminId = User::where('type_user_code', UserTypes::ADMIN)
            ->oldest()
            ->value('id') ?? null;
        $logContext = [
            'backup_type' => 'database',
            'trigger' => php_sapi_name() === 'cli' ? 'scheduled' : 'manual',
        ];

        Log::info('[Backup] بدء عملية النسخ الاحتياطي', $logContext);

        // 1. التأكد من المجلد
        if (!File::isDirectory($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        // 2. إعدادات قاعدة البيانات
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);

        // 3. اسم الملف
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $filename  = "backup_{$database}_{$timestamp}.sql.gz";
        $filepath  = "{$backupPath}/{$filename}";

        $command = "mysqldump -h {$host} -P {$port} -u {$username}"
            . (empty($password) ? '' : " -p'{$password}'")
            . " --single-transaction --routines --triggers"
            . " --add-drop-table --set-gtid-purged=OFF"
            . " {$database} 2>&1 | gzip > {$filepath}";

        try {
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                Log::error('[Backup] فشل إنشاء النسخة الاحتياطية', [
                    ...$logContext,
                    'return_code' => $returnCode,
                    'output' => implode("\n", (array) $output),
                ]);

                $this->auditService->log(
                    $adminId,
                    'backup_failed',
                    'system',
                    null,
                    null,
                    ['error' => implode("\n", (array) $output)]
                );

                $this->error('فشل إنشاء النسخة الاحتياطية!');
                return self::FAILURE;
            }

            // 4. النتيجة
            $duration = round(microtime(true) - $startTime, 2);
            $sizeMb = round(filesize($filepath) / 1024 / 1024, 2);

            Log::info('[Backup] تم إنشاء النسخة الاحتياطية بنجاح', [
                ...$logContext,
                'filename'  => $filename,
                'size_mb'   => $sizeMb,
                'duration'  => $duration,
                'keep'      => $keep,
            ]);

            $this->auditService->log(
                $adminId,
                'backup_success',
                'system',
                null,
                null,
                [
                    'filename'  => $filename,
                    'size_mb'   => $sizeMb,
                    'duration'  => $duration,
                    'trigger'   => $logContext['trigger'],
                ]
            );

            $this->info(" النسخة الاحتياطية: {$filename} ({$sizeMb} MB) — {$duration} ثانية");

            // 5. تنظيف القديمة
            $this->cleanupOldBackups($backupPath, $keep);

            return self::SUCCESS;

        } catch (Exception $e) {
            Log::error('[Backup] استثناء أثناء النسخ الاحتياطي', [
                ...$logContext,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $this->auditService->log(
                $adminId,
                'backup_failed',
                'system',
                null,
                null,
                ['exception' => $e->getMessage()]
            );

            $this->error('خطأ: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function cleanupOldBackups(string $path, int $keep): void
    {
        $files = glob("{$path}/backup_*.sql.gz");
        if (count($files) <= $keep) return;

        usort($files, fn ($a, $b) => filemtime($a) - filemtime($b));
        $toDelete = array_slice($files, 0, count($files) - $keep);
        $deleted = 0;

        foreach ($toDelete as $file) {
            if (File::delete($file)) $deleted++;
        }

        if ($deleted > 0) {
            $deletedNames = array_map(fn ($f) => basename($f), $toDelete);
            Log::info('[Backup] تم حذف نسخ قديمة', [
                'deleted_count' => $deleted,
                'deleted_files' => $deletedNames,
            ]);
            $this->warn("تم حذف {$deleted} نسخة قديمة");
        }
    }
}