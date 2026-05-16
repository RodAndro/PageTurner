<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\DB;

class CleanupPendingOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:cleanup-pending {--hours=24}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel pending orders older than specified hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $log = MaintenanceLog::create([
            'task_name' => 'order:cleanup-pending',
            'status' => 'running',
            'command' => 'order:cleanup-pending',
            'started_at' => $startTime,
        ]);

        try {
            $hours = $this->option('hours');
            $cutoffTime = now()->subHours($hours);

            $updated = DB::table('orders')
                ->where('status', 'pending')
                ->where('created_at', '<', $cutoffTime)
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'completed',
                'output' => "Cancelled {$updated} pending orders older than {$hours} hours",
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->info("Cleanup complete: {$updated} orders cancelled");
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->error("Cleanup failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
