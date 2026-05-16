<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class RotateLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:rotate {--days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive and compress old log files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $log = MaintenanceLog::create([
            'task_name' => 'log:rotate',
            'status' => 'running',
            'command' => 'log:rotate',
            'started_at' => $startTime,
        ]);

        try {
            $days = $this->option('days');
            $logsPath = storage_path('logs');
            $archivePath = storage_path('logs/archive');

            if (!File::exists($archivePath)) {
                File::makeDirectory($archivePath, 0755, true);
            }

            $cutoffDate = now()->subDays($days);
            $archived = 0;

            // Archive old log files
            foreach (File::files($logsPath) as $file) {
                if ($file->getExtension() === 'log' && $file->getMTime() < $cutoffDate->timestamp) {
                    $filename = $file->getFilename();
                    $archiveFile = $archivePath . '/' . str_replace('.log', '-' . now()->format('Y-m-d-His') . '.gz', $filename);

                    // Compress the file
                    $command = "gzip -c \"$file\" > \"$archiveFile\"";
                    exec($command);

                    // Delete original
                    File::delete($file);
                    $archived++;
                }
            }

            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'completed',
                'output' => "Archived {$archived} log files older than {$days} days",
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->info("Log rotation complete: {$archived} logs archived");
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->error("Log rotation failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
