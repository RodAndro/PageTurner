<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\BackupLog;
use App\Models\MaintenanceLog;
use App\Models\BackupHealthCheck;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BackupMaintenanceController extends Controller
{
    /**
     * Show backup and maintenance dashboard
     */
    public function dashboard(): View
    {
        $recentBackups = BackupLog::latest()->limit(10)->get();
        $failedBackups = BackupLog::failed()->limit(5)->get();
        $maintenanceTasks = MaintenanceLog::latest()->limit(15)->get();
        $healthChecks = BackupHealthCheck::all();

        $stats = [
            'total_backups' => BackupLog::count(),
            'successful_backups' => BackupLog::successful()->count(),
            'failed_backups' => BackupLog::failed()->count(),
            'total_tasks' => MaintenanceLog::count(),
            'successful_tasks' => MaintenanceLog::successful()->count(),
            'failed_tasks' => MaintenanceLog::failed()->count(),
            'unhealthy_backups' => BackupHealthCheck::unhealthy()->count(),
        ];

        return view('admin.backup-maintenance.dashboard', [
            'recentBackups' => $recentBackups,
            'failedBackups' => $failedBackups,
            'maintenanceTasks' => $maintenanceTasks,
            'healthChecks' => $healthChecks,
            'stats' => $stats,
        ]);
    }

    /**
     * Trigger manual backup
     */
    public function triggerBackup(): RedirectResponse
    {
        try {
            $startTime = now();
            $backupLog = BackupLog::create([
                'user_id' => Auth::id(),
                'type' => 'manual',
                'status' => 'processing',
                'started_at' => $startTime,
            ]);

            // Run backup using Artisan command
            Artisan::call('backup:run');

            // Get backup file information
            $backupDir = storage_path('backups');
            $backupFile = collect(File::files($backupDir))
                ->sortByDesc('getMTime')
                ->first();

            if ($backupFile) {
                $fileSizeMb = round($backupFile->getSize() / (1024 * 1024), 2);
                $backupName = $backupFile->getFilename();
            } else {
                $fileSizeMb = null;
                $backupName = 'unknown';
            }

            $duration = now()->diffInSeconds($startTime);

            $backupLog->update([
                'status' => 'completed',
                'backup_name' => $backupName,
                'file_size_mb' => $fileSizeMb,
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            return redirect()->route('admin.backup-maintenance.dashboard')
                ->with('success', "Backup completed successfully! Size: {$fileSizeMb}MB, Duration: {$duration}s");
        } catch (\Exception $e) {
            $backupLog = BackupLog::where('user_id', Auth::id())
                ->where('type', 'manual')
                ->where('status', 'processing')
                ->first();

            if ($backupLog) {
                $duration = now()->diffInSeconds($backupLog->started_at);
                $backupLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'duration_seconds' => $duration,
                    'completed_at' => now(),
                ]);
            }

            return redirect()->back()
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Show backup details
     */
    public function showBackup(int $backupId): View
    {
        $backup = BackupLog::findOrFail($backupId);

        // Verify admin owns this or can view all backups
        if ($backup->user_id !== null && $backup->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        return view('admin.backup-maintenance.backup-detail', [
            'backup' => $backup,
        ]);
    }

    /**
     * Show maintenance task details
     */
    public function showTask(int $taskId): View
    {
        $task = MaintenanceLog::findOrFail($taskId);

        return view('admin.backup-maintenance.task-detail', [
            'task' => $task,
        ]);
    }

    /**
     * Run specific maintenance task manually
     */
    public function runTask(Request $request, string $taskName): RedirectResponse
    {
        try {
            $startTime = now();
            $log = MaintenanceLog::create([
                'task_name' => $taskName,
                'status' => 'running',
                'was_manual' => true,
                'command' => $taskName,
                'started_at' => $startTime,
            ]);

            // Map task names to commands
            $commands = [
                'backup-run' => 'backup:run',
                'backup-clean' => 'backup:clean',
                'order-cleanup' => 'order:cleanup-pending',
                'session-cleanup' => 'session:cleanup',
                'log-rotate' => 'log:rotate',
                'report-generate' => 'report:generate-daily',
                'notification-prune' => 'notification:prune',
                'audit-archive' => 'audit:archive',
            ];

            if (!isset($commands[$taskName])) {
                return redirect()->back()->with('error', 'Invalid task name');
            }

            $exitCode = Artisan::call($commands[$taskName]);
            $duration = now()->diffInSeconds($startTime);

            $status = $exitCode === 0 ? 'completed' : 'failed';
            $output = Artisan::output();

            $log->update([
                'status' => $status,
                'output' => $output,
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $message = $status === 'completed'
                ? "Task '{$taskName}' completed successfully in {$duration}s"
                : "Task '{$taskName}' failed";

            return redirect()->route('admin.backup-maintenance.dashboard')
                ->with($status === 'completed' ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Task execution failed: ' . $e->getMessage());
        }
    }

    /**
     * View backup logs
     */
    public function viewBackups(Request $request): View
    {
        $query = BackupLog::query();

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('type')) {
            $query->where('type', $request->input('type'));
        }

        $backups = $query->latest()->paginate(20);

        return view('admin.backup-maintenance.backups-list', [
            'backups' => $backups,
            'statuses' => ['pending', 'processing', 'completed', 'failed'],
            'types' => ['manual', 'scheduled', 'health-check'],
        ]);
    }

    /**
     * View maintenance logs
     */
    public function viewTasks(Request $request): View
    {
        $query = MaintenanceLog::query();

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('task')) {
            $query->where('task_name', $request->input('task'));
        }

        $tasks = $query->latest()->paginate(20);

        $taskNames = MaintenanceLog::distinct('task_name')->pluck('task_name');

        return view('admin.backup-maintenance.tasks-list', [
            'tasks' => $tasks,
            'statuses' => ['pending', 'running', 'completed', 'failed'],
            'taskNames' => $taskNames,
        ]);
    }

    /**
     * Download backup file
     */
    public function downloadBackup(int $backupId)
    {
        $backup = BackupLog::findOrFail($backupId);

        if (!$backup->backup_file_path) {
            return redirect()->back()->with('error', 'Backup file not found');
        }

        $filePath = storage_path('backups/' . $backup->backup_name);

        if (!File::exists($filePath)) {
            return redirect()->back()->with('error', 'Backup file does not exist');
        }

        return response()->download($filePath, $backup->backup_name);
    }

    /**
     * Delete backup
     */
    public function deleteBackup(int $backupId): RedirectResponse
    {
        try {
            $backup = BackupLog::findOrFail($backupId);

            if ($backup->backup_file_path && File::exists(storage_path('backups/' . $backup->backup_name))) {
                File::delete(storage_path('backups/' . $backup->backup_name));
            }

            $backup->delete();

            return redirect()->route('admin.backup-maintenance.backups-list')
                ->with('success', 'Backup deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }
}
