<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BackupMonitoring;
use App\Models\ScheduledTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BackupFailureNotification;
use Carbon\Carbon;

/**
 * Backup and Scheduling Testing
 * 
 * Tests for requirement 11.2:
 * - Manual backup execution and verification
 * - Scheduled task automation (using php artisan schedule:run)
 * - Backup file integrity and restoration procedure
 * - Failure notification delivery
 * - Retention policy enforcement
 */
class BackupSchedulingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 11.2.1: Test manual backup execution
     */
    public function test_manual_backup_execution_creates_backup_file()
    {
        $response = $this->post('/admin/backups/create', [
            'backup_type' => 'database',
            'name' => 'manual_backup_' . now()->timestamp,
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('backup_path'));
    }

    /**
     * 11.2.2: Test backup file verification
     */
    public function test_backup_file_verified_with_checksum()
    {
        // Create backup
        $response = $this->post('/admin/backups/create', [
            'backup_type' => 'database',
        ]);

        $backupPath = $response->json('backup_path');
        $checksum = $response->json('checksum');

        // Verify backup
        $verifyResponse = $this->post('/admin/backups/verify', [
            'backup_path' => $backupPath,
            'checksum' => $checksum,
        ]);

        $verifyResponse->assertStatus(200);
        $this->assertTrue($verifyResponse->json('verified'));
    }

    /**
     * 11.2.3: Test scheduled task automation
     */
    public function test_scheduled_backup_task_runs_via_schedule()
    {
        // Create scheduled task
        $task = \App\Models\ScheduledTask::create([
            'task_name' => 'Daily Backup',
            'task_type' => 'backup',
            'cron_expression' => '0 2 * * *',
            'status' => 'enabled',
        ]);

        // Simulate running schedule
        $this->artisan('schedule:run');

        // Verify task was executed
        $task->refresh();
        $this->assertNotNull($task->last_run_at);
        $this->assertEquals('completed', $task->last_status);
    }

    /**
     * 11.2.4: Test backup restoration procedure
     */
    public function test_backup_restoration_restores_data()
    {
        // Create test data
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        // Create backup
        $backupResponse = $this->post('/admin/backups/create', [
            'backup_type' => 'database',
        ]);

        $backupPath = $backupResponse->json('backup_path');

        // Delete data
        $user->delete();
        $this->assertEmpty(User::where('email', 'test@example.com')->first());

        // Restore from backup
        $restoreResponse = $this->post('/admin/backups/restore', [
            'backup_path' => $backupPath,
        ]);

        $restoreResponse->assertStatus(200);
        
        // Verify data restored
        $this->assertNotEmpty(User::where('email', 'test@example.com')->first());
    }

    /**
     * 11.2.5: Test failure notifications are delivered
     */
    public function test_backup_failure_triggers_notification()
    {
        Notification::fake();

        // Simulate backup failure
        $this->post('/admin/backups/create', [
            'backup_type' => 'database',
            'force_fail' => true, // Test parameter to simulate failure
        ]);

        // Verify notification sent
        $admin = User::find(1) ?? User::factory()->create(['role' => 'admin']);
        Notification::assertSentTo(
            [$admin],
            \App\Notifications\BackupFailureNotification::class
        );
    }

    /**
     * 11.2.6: Test retention policy enforcement
     */
    public function test_old_backups_deleted_according_to_retention_policy()
    {
        // Create old backup (exceeds retention period)
        $oldBackup = \App\Models\BackupMonitoring::create([
            'backup_name' => 'old_backup',
            'backup_type' => 'database',
            'status' => 'completed',
            'backup_completed_at' => now()->subDays(35), // Older than 30 days
            'file_path' => 'backups/old_backup.zip',
        ]);

        // Create recent backup
        $recentBackup = \App\Models\BackupMonitoring::create([
            'backup_name' => 'recent_backup',
            'backup_type' => 'database',
            'status' => 'completed',
            'backup_completed_at' => now()->subDays(5), // Within retention period
            'file_path' => 'backups/recent_backup.zip',
        ]);

        // Store fake backup files
        Storage::disk('backups')->put('old_backup.zip', 'old backup content');
        Storage::disk('backups')->put('recent_backup.zip', 'recent backup content');

        // Run retention policy cleanup
        $this->artisan('backups:cleanup', [
            '--retention-days' => 30
        ]);

        // Verify old backup was deleted
        $this->assertDatabaseMissing('backup_monitoring', ['id' => $oldBackup->id]);
        $this->assertFalse(Storage::disk('backups')->exists('old_backup.zip'));

        // Verify recent backup is retained
        $this->assertDatabaseHas('backup_monitoring', ['id' => $recentBackup->id]);
        $this->assertTrue(Storage::disk('backups')->exists('recent_backup.zip'));
    }

    /**
     * 11.2.7: Test backup integrity verification
     */
    public function test_backup_integrity_verification_with_checksums()
    {
        // Create backup with known content
        $backupContent = 'database backup content';
        $expectedChecksum = hash('sha256', $backupContent);
        
        $backup = \App\Models\BackupMonitoring::create([
            'backup_name' => 'integrity_test',
            'backup_type' => 'database',
            'status' => 'completed',
            'file_path' => 'backups/integrity_test.zip',
            'checksum' => $expectedChecksum,
        ]);

        Storage::disk('backups')->put('integrity_test.zip', $backupContent);

        // Verify integrity
        $response = $this->post('/admin/backups/verify-integrity', [
            'backup_id' => $backup->id,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('integrity_valid'));
        $this->assertEquals($expectedChecksum, $response->json('verified_checksum'));
    }

    /**
     * 11.2.8: Test concurrent backup operations are prevented
     */
    public function test_concurrent_backup_operations_prevented()
    {
        // Start first backup
        $firstBackup = \App\Models\BackupMonitoring::create([
            'backup_name' => 'first_backup',
            'backup_type' => 'database',
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Attempt to start second backup
        $response = $this->post('/admin/backups/create', [
            'backup_type' => 'database',
        ]);

        // Should be rejected due to concurrent operation
        $response->assertStatus(429);
        $response->assertJson(['message' => 'Backup already in progress']);
    }

    /**
     * 11.2.9: Test backup size monitoring and alerts
     */
    public function test_backup_size_monitoring_and_alerts()
    {
        // Create backup with unusual size
        $largeBackup = \App\Models\BackupMonitoring::create([
            'backup_name' => 'large_backup',
            'backup_type' => 'database',
            'status' => 'completed',
            'size_mb' => 5000.0, // 5GB - unusually large
            'backup_completed_at' => now(),
        ]);

        // Create normal backup for comparison
        $normalBackup = \App\Models\BackupMonitoring::create([
            'backup_name' => 'normal_backup',
            'backup_type' => 'database',
            'status' => 'completed',
            'size_mb' => 150.0, // 150MB - normal
            'backup_completed_at' => now()->subDay(),
        ]);

        // Check for size anomalies
        $response = $this->get('/admin/backups/analyze-sizes');
        
        $response->assertStatus(200);
        $anomalies = $response->json('anomalies');
        
        // Should detect large backup anomaly
        $this->assertNotEmpty($anomalies);
        $this->assertArrayHasKey('size_increase', $anomalies);
        $this->assertEquals($largeBackup->id, $anomalies['size_increase']['backup_id']);
    }

    /**
     * 11.2.10: Test backup restoration from different time points
     */
    public function test_backup_restoration_from_different_time_points()
    {
        // Create backups from different time points
        $backups = [
            \App\Models\BackupMonitoring::create([
                'backup_name' => 'backup_7_days_ago',
                'backup_type' => 'database',
                'backup_completed_at' => now()->subDays(7),
                'file_path' => 'backups/backup_7_days_ago.zip',
                'status' => 'completed',
            ]),
            \App\Models\BackupMonitoring::create([
                'backup_name' => 'backup_3_days_ago',
                'backup_type' => 'database',
                'backup_completed_at' => now()->subDays(3),
                'file_path' => 'backups/backup_3_days_ago.zip',
                'status' => 'completed',
            ]),
            \App\Models\BackupMonitoring::create([
                'backup_name' => 'backup_1_day_ago',
                'backup_type' => 'database',
                'backup_completed_at' => now()->subDay(),
                'file_path' => 'backups/backup_1_day_ago.zip',
                'status' => 'completed',
            ]),
        ];

        // Store fake backup files
        foreach ($backups as $backup) {
            Storage::disk('backups')->put($backup->file_path, "backup data from {$backup->backup_completed_at}");
        }

        // Test restoration from different time points
        foreach ($backups as $backup) {
            $response = $this->post('/admin/backups/restore-to-point', [
                'backup_id' => $backup->id,
            ]);
            
            $response->assertStatus(200);
            $this->assertEquals($backup->backup_completed_at->toDateTimeString(), 
                $response->json('restored_to'));
        }
    }
}
