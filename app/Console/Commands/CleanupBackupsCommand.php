<?php

namespace App\Console\Commands;

use App\Models\BackupMonitoring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupBackupsCommand extends Command
{
    protected $signature = 'backups:cleanup {--retention-days=30}';

    protected $description = 'Delete backup monitoring rows and files older than the retention period.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('retention-days'));
        $oldBackups = BackupMonitoring::where('backup_completed_at', '<', $cutoff)->get();

        foreach ($oldBackups as $backup) {
            $path = ltrim(str_replace('backups/', '', $backup->file_path ?? ''), '/');
            if ($path !== '') {
                Storage::disk('backups')->delete($path);
            }
            $backup->delete();
        }

        $this->info("Deleted {$oldBackups->count()} old backup(s).");

        return self::SUCCESS;
    }
}
