<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\DB;

class CleanupSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired session records from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $log = MaintenanceLog::create([
            'task_name' => 'session:cleanup',
            'status' => 'running',
            'command' => 'session:cleanup',
            'started_at' => $startTime,
        ]);

        try {
            // Get session lifetime from config
            $lifetime = config('session.lifetime') * 60; // Convert minutes to seconds
            $cutoffTime = now()->subSeconds($lifetime);

            $deleted = DB::table('sessions')
                ->where('last_activity', '<', $cutoffTime->timestamp)
                ->delete();

            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'completed',
                'output' => "Deleted {$deleted} expired session records",
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->info("Session cleanup complete: {$deleted} sessions removed");
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->error("Session cleanup failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
