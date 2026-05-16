<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\DB;

class ArchiveAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:archive {--months=12}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive audit logs older than specified months';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $log = MaintenanceLog::create([
            'task_name' => 'audit:archive',
            'status' => 'running',
            'command' => 'audit:archive',
            'started_at' => $startTime,
        ]);

        try {
            $months = $this->option('months');
            $cutoffDate = now()->subMonths($months);

            // Create archive table if it doesn't exist
            if (!DB::connection()->getSchemaBuilder()->hasTable('audit_logs_archive')) {
                DB::statement('CREATE TABLE audit_logs_archive LIKE audit_logs');
            }

            // Move old audit logs to archive
            $archived = DB::table('audit_logs')
                ->where('created_at', '<', $cutoffDate)
                ->count();

            // Use raw SQL INSERT...SELECT for efficiency
            if ($archived > 0) {
                DB::statement(
                    'INSERT INTO audit_logs_archive SELECT * FROM audit_logs WHERE created_at < ?',
                    [$cutoffDate]
                );
            }

            DB::table('audit_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'completed',
                'output' => "Archived {$archived} audit log records older than {$months} months",
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->info("Audit archiving complete: {$archived} records archived");
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->error("Audit archiving failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
