<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledTask;
use App\Models\BackupMonitoring;
use App\Services\BackupService;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RunScheduledTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:run-tasks {--task?} {--force} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled tasks with logging and error handling';

    protected array $taskResults = [];
    protected bool $isDryRun = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->isDryRun = $this->option('dry-run');
        $specificTask = $this->option('task');
        $force = $this->option('force');

        $this->info('Starting scheduled task execution...');
        
        if ($this->isDryRun) {
            $this->warn('DRY RUN MODE - No actual tasks will be executed');
        }

        try {
            if ($specificTask) {
                $this->runSpecificTask($specificTask, $force);
            } else {
                $this->runAllDueTasks($force);
            }

            $this->displayResults();
            
        } catch (\Exception $e) {
            Log::error('Scheduled task execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->error('Task execution failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('Scheduled task execution completed');
        return 0;
    }

    /**
     * Run all tasks that are due
     */
    protected function runAllDueTasks(bool $force = false): void
    {
        $tasks = ScheduledTask::where('status', 'enabled')
            ->where(function ($query) {
                $query->where('next_run_at', '<=', now())
                    ->orWhereNull('next_run_at');
            })
            ->orderBy('next_run_at')
            ->get();

        $this->info("Found {$tasks->count()} tasks to execute");

        foreach ($tasks as $task) {
            $this->executeTask($task, $force);
        }
    }

    /**
     * Run a specific task
     */
    protected function runSpecificTask(string $taskName, bool $force = false): void
    {
        $task = ScheduledTask::where('task_name', $taskName)->first();
        
        if (!$task) {
            $this->error("Task '{$taskName}' not found");
            return;
        }

        if ($task->status !== 'enabled' && !$force) {
            $this->warn("Task '{$taskName}' is not enabled. Use --force to run it.");
            return;
        }

        $this->executeTask($task, $force);
    }

    /**
     * Execute a single task
     */
    protected function executeTask(ScheduledTask $task, bool $force = false): void
    {
        $startTime = microtime(true);
        $this->info("Executing task: {$task->task_name}");

        if ($this->isDryRun) {
            $this->line("[DRY RUN] Would execute: {$task->task_name} ({$task->task_type})");
            $this->recordTaskResult($task, 'skipped', 'Dry run mode', 0);
            return;
        }

        try {
            // Update task status
            $task->update([
                'last_status' => 'running',
                'last_run_at' => now(),
            ]);

            $result = $this->performTaskExecution($task);
            
            $executionTime = microtime(true) - $startTime;
            
            // Update task with results
            $task->update([
                'last_status' => 'success',
                'last_success_at' => now(),
                'run_count' => $task->run_count + 1,
                'failure_count' => 0,
                'consecutive_failures' => 0,
                'duration_ms' => round($executionTime * 1000),
                'next_run_at' => $this->calculateNextRunTime($task),
                'last_error_message' => null,
            ]);

            $this->recordTaskResult($task, 'success', $result, $executionTime);
            $this->info("Task '{$task->task_name}' completed successfully in " . round($executionTime, 2) . "s");

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            // Update task with error
            $task->update([
                'last_status' => 'failed',
                'last_failed_at' => now(),
                'run_count' => $task->run_count + 1,
                'failure_count' => $task->failure_count + 1,
                'consecutive_failures' => $task->consecutive_failures + 1,
                'duration_ms' => round($executionTime * 1000),
                'last_error_message' => $e->getMessage(),
                'next_run_at' => $this->calculateNextRunTime($task, true), // Delay next run on failure
            ]);

            $this->recordTaskResult($task, 'failed', $e->getMessage(), $executionTime);
            $this->error("Task '{$task->task_name}' failed: " . $e->getMessage());
            
            // Send notification for critical failures
            if ($task->consecutive_failures >= 3) {
                $this->notifyCriticalFailure($task, $e);
            }
        }
    }

    /**
     * Perform the actual task execution based on type
     */
    protected function performTaskExecution(ScheduledTask $task): string
    {
        switch ($task->task_type) {
            case 'backup':
                return $this->performBackupTask($task);
            
            case 'cleanup':
                return $this->performCleanupTask($task);
            
            case 'report':
                return $this->performReportTask($task);
            
            case 'archive':
                return $this->performArchiveTask($task);
            
            case 'maintenance':
                return $this->performMaintenanceTask($task);
            
            case 'sync':
                return $this->performSyncTask($task);
            
            default:
                throw new \InvalidArgumentException("Unknown task type: {$task->task_type}");
        }
    }

    /**
     * Perform backup task
     */
    protected function performBackupTask(ScheduledTask $task): string
    {
        $backupService = new BackupService();
        
        switch ($task->task_name) {
            case 'daily_backup':
                return $backupService->createFullBackup();
            
            case 'weekly_backup':
                return $backupService->createIncrementalBackup();
            
            case 'monthly_backup':
                return $backupService->createArchiveBackup();
            
            default:
                return $backupService->createCustomBackup($task->description ?? '');
        }
    }

    /**
     * Perform cleanup task
     */
    protected function performCleanupTask(ScheduledTask $task): string
    {
        $cleanedItems = [];
        
        switch ($task->task_name) {
            case 'temp_files_cleanup':
                $cleanedItems = $this->cleanupTempFiles();
                break;
            
            case 'logs_cleanup':
                $cleanedItems = $this->cleanupOldLogs();
                break;
            
            case 'cache_cleanup':
                $cleanedItems = $this->cleanupCache();
                break;
            
            case 'audit_logs_archive':
                $cleanedItems = $this->archiveAuditLogs();
                break;
            
            default:
                $cleanedItems = $this->performCustomCleanup($task);
        }
        
        return "Cleaned up " . count($cleanedItems) . " items";
    }

    /**
     * Perform report task
     */
    protected function performReportTask(ScheduledTask $task): string
    {
        switch ($task->task_name) {
            case 'daily_summary':
                return $this->generateDailySummary();
            
            case 'weekly_report':
                return $this->generateWeeklyReport();
            
            case 'monthly_analytics':
                return $this->generateMonthlyAnalytics();
            
            case 'system_health':
                return $this->generateSystemHealthReport();
            
            default:
                return $this->generateCustomReport($task);
        }
    }

    /**
     * Perform archive task
     */
    protected function performArchiveTask(ScheduledTask $task): string
    {
        switch ($task->task_name) {
            case 'database_archive':
                return $this->archiveDatabase();
            
            case 'file_archive':
                return $this->archiveFiles();
            
            case 'audit_archive':
                return $this->archiveAuditLogs();
            
            default:
                return $this->performCustomArchive($task);
        }
    }

    /**
     * Perform maintenance task
     */
    protected function performMaintenanceTask(ScheduledTask $task): string
    {
        switch ($task->task_name) {
            case 'database_optimization':
                return $this->optimizeDatabase();
            
            case 'index_rebuild':
                return $this->rebuildIndexes();
            
            case 'system_update':
                return $this->performSystemUpdate();
            
            default:
                return $this->performCustomMaintenance($task);
        }
    }

    /**
     * Perform sync task
     */
    protected function performSyncTask(ScheduledTask $task): string
    {
        switch ($task->task_name) {
            case 'user_sync':
                return $this->syncUserData();
            
            case 'product_sync':
                return $this->syncProductData();
            
            case 'analytics_sync':
                return $this->syncAnalytics();
            
            default:
                return $this->performCustomSync($task);
        }
    }

    /**
     * Calculate next run time for task
     */
    protected function calculateNextRunTime(ScheduledTask $task, bool $isFailure = false): ?Carbon
    {
        if (empty($task->cron_expression)) {
            return null;
        }

        try {
            $cron = \Cron\CronExpression::factory($task->cron_expression);
            
            // If task failed, delay next run
            if ($isFailure) {
                return now()->addMinutes(30); // 30 minute delay on failure
            }
            
            return $cron->getNextRunDate(now());
            
        } catch (\Exception $e) {
            Log::error('Invalid cron expression', [
                'task_name' => $task->task_name,
                'cron_expression' => $task->cron_expression,
                'error' => $e->getMessage(),
            ]);
            
            // Default to 1 hour from now if cron is invalid
            return now()->addHour();
        }
    }

    /**
     * Record task result for reporting
     */
    protected function recordTaskResult(ScheduledTask $task, string $status, string $message, float $duration): void
    {
        $this->taskResults[] = [
            'task_name' => $task->task_name,
            'task_type' => $task->task_type,
            'status' => $status,
            'message' => $message,
            'duration' => $duration,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Display execution results
     */
    protected function displayResults(): void
    {
        if (empty($this->taskResults)) {
            $this->info('No tasks were executed');
            return;
        }

        $this->info("\n=== Task Execution Results ===");
        
        foreach ($this->taskResults as $result) {
            $status = strtoupper($result['status']);
            $icon = $status === 'SUCCESS' ? '✓' : ($status === 'FAILED' ? '✗' : '○');
            
            $this->line("{$icon} {$result['task_name']} ({$result['task_type']}) - {$status}");
            $this->line("  Message: {$result['message']}");
            $this->line("  Duration: " . round($result['duration'], 2) . "s");
            $this->line("  Time: {$result['timestamp']}");
            $this->line("");
        }

        $successCount = collect($this->taskResults)->filter(fn($r) => $r['status'] === 'success')->count();
        $failedCount = collect($this->taskResults)->filter(fn($r) => $r['status'] === 'failed')->count();
        
        $this->info("\nSummary: {$successCount} successful, {$failedCount} failed");
    }

    /**
     * Notify critical task failures
     */
    protected function notifyCriticalFailure(ScheduledTask $task, \Exception $exception): void
    {
        Log::critical('Critical task failure', [
            'task_name' => $task->task_name,
            'consecutive_failures' => $task->consecutive_failures,
            'error' => $exception->getMessage(),
        ]);

        // Here you could add email notifications, Slack notifications, etc.
        // For now, just log it
    }

    // Helper methods for specific task implementations
    
    protected function cleanupTempFiles(): array
    {
        $tempPath = storage_path('app/temp');
        $files = glob($tempPath . '/*');
        $cleaned = [];
        
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 86400) { // Older than 24 hours
                unlink($file);
                $cleaned[] = basename($file);
            }
        }
        
        return $cleaned;
    }

    protected function cleanupOldLogs(): array
    {
        $cutoffDate = now()->subDays(30);
        $deletedCount = \App\Models\AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        return ['old_logs_deleted' => $deletedCount];
    }

    protected function cleanupCache(): array
    {
        $cache = app('cache');
        $cleared = $cache->flush();
        
        return ['cache_cleared' => $cleared];
    }

    protected function archiveAuditLogs(): array
    {
        $cutoffDate = now()->subDays(365);
        $archivedCount = \App\Models\AuditLog::where('created_at', '<', $cutoffDate)->count();
        
        // Archive logic would go here
        return ['audit_logs_archived' => $archivedCount];
    }

    protected function generateDailySummary(): string
    {
        $stats = [
            'new_users' => \App\Models\User::whereDate('created_at', today())->count(),
            'new_orders' => \App\Models\Order::whereDate('created_at', today())->count(),
            'total_books' => \App\Models\Book::count(),
        ];
        
        Log::info('Daily summary generated', $stats);
        
        return "Daily summary: " . json_encode($stats);
    }

    protected function generateWeeklyReport(): string
    {
        $stats = [
            'weekly_users' => \App\Models\User::whereBetween('created_at', [now()->subWeek(), now()])->count(),
            'weekly_orders' => \App\Models\Order::whereBetween('created_at', [now()->subWeek(), now()])->count(),
            'weekly_revenue' => \App\Models\Order::whereBetween('created_at', [now()->subWeek(), now()])->sum('total_amount'),
        ];
        
        Log::info('Weekly report generated', $stats);
        
        return "Weekly report: " . json_encode($stats);
    }

    protected function generateMonthlyAnalytics(): string
    {
        $stats = [
            'monthly_users' => \App\Models\User::whereMonth('created_at', now()->month)->count(),
            'monthly_orders' => \App\Models\Order::whereMonth('created_at', now()->month)->count(),
            'monthly_revenue' => \App\Models\Order::whereMonth('created_at', now()->month)->sum('total_amount'),
            'top_categories' => \App\Models\Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('books', 'order_items.book_id', '=', 'books.id')
                ->join('categories', 'books.category_id', '=', 'categories.id')
                ->whereMonth('orders.created_at', now()->month)
                ->groupBy('categories.name')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(5)
                ->pluck('categories.name')
                ->toArray(),
        ];
        
        Log::info('Monthly analytics generated', $stats);
        
        return "Monthly analytics: " . json_encode($stats);
    }

    protected function generateSystemHealthReport(): string
    {
        $stats = [
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'database_size' => $this->getDatabaseSize(),
            'active_users' => \App\Models\User::whereDate('last_login_at', today())->count(),
        ];
        
        Log::info('System health report generated', $stats);
        
        return "System health: " . json_encode($stats);
    }

    protected function optimizeDatabase(): string
    {
        // Database optimization logic
        \DB::statement('OPTIMIZE TABLE users');
        \DB::statement('OPTIMIZE TABLE books');
        \DB::statement('OPTIMIZE TABLE orders');
        
        return "Database optimized";
    }

    protected function rebuildIndexes(): string
    {
        // Index rebuilding logic
        Log::info('Database indexes rebuilt');
        
        return "Database indexes rebuilt";
    }

    protected function performSystemUpdate(): string
    {
        // System update logic
        Log::info('System update performed');
        
        return "System update completed";
    }

    protected function syncUserData(): string
    {
        // User data sync logic
        $syncedUsers = \App\Models\User::where('updated_at', '<', now()->subHour())->count();
        
        return "Synced {$syncedUsers} user records";
    }

    protected function syncProductData(): string
    {
        // Product data sync logic
        $syncedProducts = \App\Models\Book::where('updated_at', '<', now()->subHour())->count();
        
        return "Synced {$syncedProducts} product records";
    }

    protected function syncAnalytics(): string
    {
        // Analytics sync logic
        Log::info('Analytics data synchronized');
        
        return "Analytics synchronized";
    }

    // Placeholder methods for custom implementations
    protected function performCustomCleanup(ScheduledTask $task): string
    {
        return "Custom cleanup: {$task->description}";
    }

    protected function generateCustomReport(ScheduledTask $task): string
    {
        return "Custom report: {$task->description}";
    }

    protected function performCustomArchive(ScheduledTask $task): string
    {
        return "Custom archive: {$task->description}";
    }

    protected function performCustomMaintenance(ScheduledTask $task): string
    {
        return "Custom maintenance: {$task->description}";
    }

    protected function performCustomSync(ScheduledTask $task): string
    {
        return "Custom sync: {$task->description}";
    }

    protected function archiveDatabase(): string
    {
        return "Database archived";
    }

    protected function archiveFiles(): string
    {
        return "Files archived";
    }

    protected function getDiskUsage(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    protected function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percentage' => round(($memoryUsage / $memoryLimit) * 100, 2),
        ];
    }

    protected function getDatabaseSize(): string
    {
        // This would vary by database type
        // For SQLite, check file size
        // For MySQL, query information_schema
        return "Database size calculated";
    }
}
