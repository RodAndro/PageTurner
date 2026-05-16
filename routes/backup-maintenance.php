<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupMaintenanceController;

Route::middleware(['auth', 'verified', 'access_control:admin'])->group(function () {
    Route::prefix('admin/backup-maintenance')->name('admin.backup-maintenance.')->group(function () {
        // Dashboard
        Route::get('/', [BackupMaintenanceController::class, 'dashboard'])->name('dashboard');

        // Backup routes
        Route::get('/backups', [BackupMaintenanceController::class, 'viewBackups'])->name('backups-list');
        Route::get('/backups/{backupId}', [BackupMaintenanceController::class, 'showBackup'])->name('backup-detail');
        Route::post('/backups/trigger', [BackupMaintenanceController::class, 'triggerBackup'])->name('trigger-backup');
        Route::get('/backups/{backupId}/download', [BackupMaintenanceController::class, 'downloadBackup'])->name('download-backup');
        Route::delete('/backups/{backupId}', [BackupMaintenanceController::class, 'deleteBackup'])->name('delete-backup');

        // Maintenance task routes
        Route::get('/tasks', [BackupMaintenanceController::class, 'viewTasks'])->name('tasks-list');
        Route::get('/tasks/{taskId}', [BackupMaintenanceController::class, 'showTask'])->name('task-detail');
        Route::post('/tasks/{taskName}/run', [BackupMaintenanceController::class, 'runTask'])->name('run-task');
    });
});
