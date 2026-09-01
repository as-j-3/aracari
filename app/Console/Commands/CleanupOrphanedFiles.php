<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProviderFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup {--days=30 : Delete files soft-deleted before this many days} {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned files from storage that are soft-deleted in database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = now()->subDays($days);

        $this->info("Looking for files soft-deleted before: {$cutoffDate->format('Y-m-d H:i:s')}");

        // Get soft-deleted files older than the cutoff date
        $deletedFiles = ProviderFile::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();

        if ($deletedFiles->isEmpty()) {
            $this->info('No orphaned files found for cleanup.');
            return Command::SUCCESS;
        }

        $this->info("Found {$deletedFiles->count()} orphaned files to clean up.");

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($deletedFiles as $file) {
            $filePath = $file->file;
            
            try {
                if ($dryRun) {
                    $this->line("[DRY-RUN] Would delete: {$filePath}");
                    $deletedCount++;
                    continue;
                }

                // Check if file exists in storage before attempting to delete
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                    Log::info('Deleted orphaned file from storage', [
                        'file_path' => $filePath,
                        'provider_file_id' => $file->id,
                    ]);
                }

                // Permanently delete the record from database
                $file->forceDelete();
                
                $this->line("✓ Deleted: {$filePath}");
                $deletedCount++;

            } catch (\Exception $e) {
                Log::error('Failed to delete orphaned file', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
                
                $this->error("✗ Failed to delete: {$filePath} - {$e->getMessage()}");
                $failedCount++;
            }
        }

        if ($dryRun) {
            $this->info("\n[DRY-RUN] Would delete {$deletedCount} files.");
        } else {
            $this->info("\nCleanup completed: {$deletedCount} files deleted, {$failedCount} failed.");
        }

        return Command::SUCCESS;
    }
}
