<?php

namespace App\Http\Controllers\Admin;

use App\Models\ImportLog;
use App\Models\ExportLog;
use App\Models\BackupMonitoring;
use App\Models\ScheduledTask;
use App\Models\ApiRateLimit;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Admin Dashboard Controller
 * 
 * Manages the admin dashboard with data management widgets including:
 * - Import/Export status
 * - Backup monitoring
 * - Audit log summary
 * - API usage statistics
 * - System health metrics
 * 
 * @package App\Http\Controllers\Admin
 */
class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $widgets = [
            'import_export' => $this->getImportExportStatus(),
            'backup' => $this->getBackupStatus(),
            'audit_logs' => $this->getAuditLogSummary(),
            'api_usage' => $this->getApiUsageStatistics(),
            'system_health' => $this->getSystemHealth(),
        ];

        return view('admin.dashboard.index', $widgets);
    }

    /**
     * Widget: Import/Export Status
     * 
     * Displays recent import/export operations and statistics
     * 
     * @return array
     */
    private function getImportExportStatus()
    {
        $recentImports = ImportLog::latest()
            ->with('user')
            ->limit(5)
            ->get();

        $recentExports = ExportLog::latest()
            ->with('user')
            ->limit(5)
            ->get();

        // Calculate statistics
        $importStats = [
            'total_operations' => ImportLog::whereDate('created_at', now())->count(),
            'success_rate' => $this->calculateImportSuccessRate(),
            'in_progress' => ImportLog::where('status', 'processing')->count(),
            'failed_today' => ImportLog::where('status', 'failed')->whereDate('created_at', now())->count(),
        ];

        $exportStats = [
            'total_operations' => ExportLog::whereDate('created_at', now())->count(),
            'completed_today' => ExportLog::where('status', 'completed')->whereDate('created_at', now())->count(),
            'in_progress' => ExportLog::where('status', 'processing')->count(),
            'failed' => ExportLog::where('status', 'failed')->whereDate('created_at', now())->count(),
        ];

        return [
            'recent_imports' => $recentImports,
            'recent_exports' => $recentExports,
            'import_stats' => $importStats,
            'export_stats' => $exportStats,
        ];
    }

    /**
     * Widget: Backup Status
     * 
     * Displays latest backup information and health
     * 
     * @return array
     */
    private function getBackupStatus()
    {
        $latestBackup = BackupMonitoring::latest('backup_completed_at')->first();
        
        $backupStats = [
            'total_backups' => BackupMonitoring::count(),
            'verified_backups' => BackupMonitoring::where('is_verified', true)->count(),
            'failed_backups' => BackupMonitoring::where('status', 'failed')->count(),
            'corrupted_backups' => BackupMonitoring::where('status', 'corrupted')->count(),
            'in_progress' => BackupMonitoring::where('status', 'in_progress')->count(),
        ];

        $storageUsage = BackupMonitoring::where('status', '!=', 'failed')
            ->sum('size_mb');

        $nextScheduledBackup = BackupMonitoring::where('status', 'pending')
            ->orWhere('status', 'completed')
            ->latest('next_backup_at')
            ->first();

        return [
            'latest_backup' => $latestBackup,
            'backup_stats' => $backupStats,
            'storage_usage_mb' => $storageUsage,
            'next_scheduled' => $nextScheduledBackup,
            'health_status' => $this->calculateBackupHealth(),
        ];
    }

    /**
     * Widget: Audit Log Summary
     * 
     * Displays recent critical events and security alerts
     * 
     * @return array
     */
    private function getAuditLogSummary()
    {
        $criticalEvents = AuditLog::where('is_critical', true)
            ->latest()
            ->limit(10)
            ->with('user')
            ->get();

        $securityAlerts = AuditLog::whereIn('action', ['login_failed', 'unauthorized_access', 'permission_denied'])
            ->whereDate('created_at', now())
            ->limit(5)
            ->with('user')
            ->get();

        $auditStats = [
            'total_events_today' => AuditLog::whereDate('created_at', now())->count(),
            'critical_events_today' => AuditLog::where('is_critical', true)
                ->whereDate('created_at', now())
                ->count(),
            'security_alerts_today' => $securityAlerts->count(),
            'users_active_today' => AuditLog::whereDate('created_at', now())
                ->distinct('user_id')
                ->count(),
        ];

        return [
            'critical_events' => $criticalEvents,
            'security_alerts' => $securityAlerts,
            'audit_stats' => $auditStats,
        ];
    }

    /**
     * Widget: API Usage Statistics
     * 
     * Displays API request patterns and rate limiting metrics
     * 
     * @return array
     */
    private function getApiUsageStatistics()
    {
        $throttledUsers = ApiRateLimit::where('is_throttled', true)
            ->with('user')
            ->limit(10)
            ->get();

        $blockedIps = ApiRateLimit::where('status', 'blocked')
            ->limit(5)
            ->get();

        $topEndpoints = ApiRateLimit::selectRaw('endpoint, COUNT(*) as request_count, SUM(requests_count) as total_requests')
            ->groupBy('endpoint')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get();

        $apiStats = [
            'total_requests_today' => ApiRateLimit::whereDate('created_at', now())->sum('requests_count') ?? 0,
            'throttled_today' => ApiRateLimit::where('is_throttled', true)
                ->whereDate('created_at', now())
                ->count(),
            'blocked_ips' => ApiRateLimit::where('status', 'blocked')->count(),
            'error_rate' => $this->calculateApiErrorRate(),
        ];

        return [
            'throttled_users' => $throttledUsers,
            'blocked_ips' => $blockedIps,
            'top_endpoints' => $topEndpoints,
            'api_stats' => $apiStats,
        ];
    }

    /**
     * Widget: System Health
     * 
     * Displays overall system health metrics
     * 
     * @return array
     */
    private function getSystemHealth()
    {
        // Scheduled tasks health
        $scheduledTasks = ScheduledTask::all();
        
        $taskHealth = [
            'enabled' => $scheduledTasks->where('status', 'enabled')->count(),
            'disabled' => $scheduledTasks->where('status', 'disabled')->count(),
            'failures_24h' => ScheduledTask::where('consecutive_failures', '>', 0)->count(),
            'critical_tasks_failed' => ScheduledTask::where('consecutive_failures', '>=', 3)->count(),
        ];

        // Database health
        $dbHealth = [
            'table_count' => $this->getTableCount(),
            'estimated_size_mb' => $this->getEstimatedDbSize(),
            'backup_status' => BackupMonitoring::where('is_verified', true)->latest()->first()?->status ?? 'unknown',
        ];

        // Queue health
        $queueHealth = [
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'queue_latency_ms' => $this->getQueueLatency(),
        ];

        // Overall health status
        $healthStatus = $this->calculateOverallHealth($taskHealth, $dbHealth, $queueHealth);

        return [
            'task_health' => $taskHealth,
            'db_health' => $dbHealth,
            'queue_health' => $queueHealth,
            'overall_health' => $healthStatus,
            'timestamp' => now(),
        ];
    }

    /**
     * Calculate import success rate percentage
     */
    private function calculateImportSuccessRate()
    {
        $totalImports = ImportLog::whereDate('created_at', now())->count();
        if ($totalImports === 0) {
            return 100;
        }

        $successfulImports = ImportLog::where('status', 'completed')
            ->whereDate('created_at', now())
            ->count();

        return round(($successfulImports / $totalImports) * 100, 2);
    }

    /**
     * Calculate backup health status
     */
    private function calculateBackupHealth()
    {
        $recentBackups = BackupMonitoring::where('is_verified', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($recentBackups >= 5) {
            return 'excellent';
        } elseif ($recentBackups >= 3) {
            return 'good';
        } elseif ($recentBackups >= 1) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Calculate API error rate
     */
    private function calculateApiErrorRate()
    {
        $totalRequests = ApiRateLimit::whereDate('created_at', now())
            ->sum('requests_count') ?? 1;

        $errorRequests = ApiRateLimit::where('status', 'blocked')
            ->whereDate('created_at', now())
            ->sum('requests_count') ?? 0;

        return $totalRequests > 0 ? round(($errorRequests / $totalRequests) * 100, 2) : 0;
    }

    /**
     * Get total table count
     */
    private function getTableCount()
    {
        // This would vary by database driver
        // For SQLite, query sqlite_master
        // For MySQL, query information_schema.tables
        return 15; // Placeholder - would be dynamic based on DB
    }

    /**
     * Get estimated database size
     */
    private function getEstimatedDbSize()
    {
        // Placeholder - would calculate from actual database
        $backupSize = BackupMonitoring::where('status', 'verified')
            ->latest()
            ->first()?->size_mb ?? 0;

        return round($backupSize, 2);
    }

    /**
     * Get pending jobs count
     */
    private function getPendingJobsCount()
    {
        // Would check queue system (Redis, database, etc.)
        return 0; // Placeholder
    }

    /**
     * Get failed jobs count
     */
    private function getFailedJobsCount()
    {
        // Would check failed_jobs table or queue system
        return 0; // Placeholder
    }

    /**
     * Get queue latency in milliseconds
     */
    private function getQueueLatency()
    {
        // Would measure actual queue processing time
        return 125; // Placeholder - milliseconds
    }

    /**
     * Calculate overall health status
     */
    private function calculateOverallHealth($taskHealth, $dbHealth, $queueHealth)
    {
        $healthScores = [];

        // Task health score
        if ($taskHealth['critical_tasks_failed'] > 0) {
            $healthScores[] = 'critical';
        } elseif ($taskHealth['failures_24h'] > 0) {
            $healthScores[] = 'warning';
        } else {
            $healthScores[] = 'healthy';
        }

        // Backup health score
        $healthScores[] = $this->calculateBackupHealth() === 'excellent' ? 'healthy' : 'warning';

        // Queue health
        if ($queueHealth['failed_jobs'] > 100) {
            $healthScores[] = 'critical';
        } elseif ($queueHealth['pending_jobs'] > 1000) {
            $healthScores[] = 'warning';
        } else {
            $healthScores[] = 'healthy';
        }

        // Determine overall status
        if (in_array('critical', $healthScores)) {
            return 'critical';
        } elseif (in_array('warning', $healthScores)) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }
}
