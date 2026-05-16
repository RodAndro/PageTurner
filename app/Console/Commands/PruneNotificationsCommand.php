<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceLog;
use Illuminate\Notifications\DatabaseNotification;

class PruneNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:prune {--days=90}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old notification records from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $log = MaintenanceLog::create([
            'task_name' => 'notification:prune',
            'status' => 'running',
            'command' => 'notification:prune',
            'started_at' => $startTime,
        ]);

        try {
            $days = $this->option('days');
            $cutoffDate = now()->subDays($days);

            $deleted = DatabaseNotification::where('created_at', '<', $cutoffDate)->delete();

            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'completed',
                'output' => "Pruned {$deleted} notification records older than {$days} days",
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->info("Notification pruning complete: {$deleted} notifications removed");
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->error("Notification pruning failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
