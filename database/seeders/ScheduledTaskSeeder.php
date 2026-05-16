<?php

namespace Database\Seeders;

use App\Models\ScheduledTask;
use Illuminate\Database\Seeder;

class ScheduledTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample scheduled tasks for system operations.
     */
    public function run(): void
    {
        // Backup task
        ScheduledTask::create([
            'task_name' => 'database_backup',
            'description' => 'Full database backup to external storage',
            'task_type' => 'backup',
            'cron_expression' => '0 2 * * *', // 2 AM daily
            'status' => 'enabled',
            'last_status' => 'success',
            'last_run_at' => now()->subHours(22),
            'last_success_at' => now()->subHours(22),
            'last_failed_at' => null,
            'run_count' => 45,
            'failure_count' => 2,
            'duration_ms' => 8420,
            'last_error_message' => null,
            'consecutive_failures' => 0,
            'next_run_at' => now()->addHours(2),
        ]);

        // Audit log archival task
        ScheduledTask::create([
            'task_name' => 'audit_log_archive',
            'description' => 'Archive old audit logs to cold storage',
            'task_type' => 'archive',
            'cron_expression' => '0 3 1 * *', // 3 AM on 1st of month
            'status' => 'enabled',
            'last_status' => 'success',
            'last_run_at' => now()->subDays(18),
            'last_success_at' => now()->subDays(18),
            'last_failed_at' => null,
            'run_count' => 6,
            'failure_count' => 0,
            'duration_ms' => 15230,
            'last_error_message' => null,
            'consecutive_failures' => 0,
            'next_run_at' => now()->addDays(13),
        ]);

        // Cache cleanup task
        ScheduledTask::create([
            'task_name' => 'cache_cleanup',
            'description' => 'Clean expired cache entries',
            'task_type' => 'cleanup',
            'cron_expression' => '*/30 * * * *', // Every 30 minutes
            'status' => 'enabled',
            'last_status' => 'success',
            'last_run_at' => now()->subMinutes(15),
            'last_success_at' => now()->subMinutes(15),
            'last_failed_at' => null,
            'run_count' => 1245,
            'failure_count' => 3,
            'duration_ms' => 245,
            'last_error_message' => null,
            'consecutive_failures' => 0,
            'next_run_at' => now()->addMinutes(15),
        ]);

        // Report generation task
        ScheduledTask::create([
            'task_name' => 'generate_daily_reports',
            'description' => 'Generate daily business reports',
            'task_type' => 'report',
            'cron_expression' => '0 4 * * *', // 4 AM daily
            'status' => 'enabled',
            'last_status' => 'failed',
            'last_run_at' => now()->subHours(20),
            'last_success_at' => now()->subDays(2)->subHours(20),
            'last_failed_at' => now()->subHours(20),
            'run_count' => 89,
            'failure_count' => 5,
            'duration_ms' => 3450,
            'last_error_message' => 'Failed to connect to reporting database',
            'consecutive_failures' => 1,
            'next_run_at' => now()->addHours(4),
        ]);

        // Database maintenance task
        ScheduledTask::create([
            'task_name' => 'database_maintenance',
            'description' => 'Database optimization and index rebuild',
            'task_type' => 'maintenance',
            'cron_expression' => '0 5 * * 0', // 5 AM every Sunday
            'status' => 'enabled',
            'last_status' => 'success',
            'last_run_at' => now()->subDays(2)->subHours(19),
            'last_success_at' => now()->subDays(2)->subHours(19),
            'last_failed_at' => null,
            'run_count' => 18,
            'failure_count' => 0,
            'duration_ms' => 12340,
            'last_error_message' => null,
            'consecutive_failures' => 0,
            'next_run_at' => now()->addDays(4)->addHours(5),
        ]);

        // Sync with external systems
        ScheduledTask::create([
            'task_name' => 'sync_external_systems',
            'description' => 'Synchronize data with external APIs',
            'task_type' => 'sync',
            'cron_expression' => '*/60 * * * *', // Every hour
            'status' => 'enabled',
            'last_status' => 'success',
            'last_run_at' => now()->subHours(1),
            'last_success_at' => now()->subHours(1),
            'last_failed_at' => now()->subHours(5),
            'run_count' => 456,
            'failure_count' => 12,
            'duration_ms' => 2150,
            'last_error_message' => null,
            'consecutive_failures' => 0,
            'next_run_at' => now()->addHour(),
        ]);

        // Disabled backup verification task
        ScheduledTask::create([
            'task_name' => 'verify_backups',
            'description' => 'Verify backup integrity and completeness',
            'task_type' => 'backup',
            'cron_expression' => '0 6 * * *', // 6 AM daily
            'status' => 'disabled',
            'last_status' => 'failed',
            'last_run_at' => now()->subDays(30),
            'last_success_at' => now()->subDays(60),
            'last_failed_at' => now()->subDays(30),
            'run_count' => 5,
            'failure_count' => 3,
            'duration_ms' => 5420,
            'last_error_message' => 'Backup verification service offline',
            'consecutive_failures' => 3,
            'next_run_at' => null,
        ]);
    }
}
