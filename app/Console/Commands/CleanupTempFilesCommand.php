<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupTempFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup-temp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary uploaded files older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = Storage::disk('public');
        $directory = 'temp';

        if (!$disk->exists($directory)) {
            $this->info("Directory {$directory} does not exist. Nothing to clean.");
            return 0;
        }

        $files = $disk->allFiles($directory);
        $deletedCount = 0;
        
        // 24 hours ago timestamp
        $threshold = Carbon::now()->subHours(24)->timestamp;

        foreach ($files as $file) {
            try {
                $lastModified = $disk->lastModified($file);

                if ($lastModified < $threshold) {
                    $disk->delete($file);
                    $deletedCount++;
                    $this->line("Deleted stale temp file: {$file}");
                }
            } catch (\Exception $e) {
                $this->error("Failed to delete {$file}: " . $e->getMessage());
            }
        }

        $this->info("Cleanup complete. Deleted {$deletedCount} temporary file(s).");
        return 0;
    }
}
