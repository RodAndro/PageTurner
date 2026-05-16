<?php

namespace Database\Seeders;

use App\Models\BackupMonitoring;
use Illuminate\Database\Seeder;

class BackupMonitoringSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample backup monitoring records for testing.
     */
    public function run(): void
    {
        // Successful full backup - verified
        BackupMonitoring::create([
            'backup_name' => 'full_backup_2026_04_19',
            'backup_type' => 'full',
            'status' => 'verified',
            'source_location' => '/var/lib/mysql/pageturner_db',
            'destination_location' => '/backups/s3://pageturner-backups/full_backup_2026_04_19.tar.gz',
            'size_mb' => 456.25,
            'duration_seconds' => 342.50,
            'total_files' => 128,
            'total_tables' => 15,
            'compression_type' => 'gzip',
            'checksum' => 'sha256:a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6',
            'is_encrypted' => true,
            'is_verified' => true,
            'backup_started_at' => now()->subDays(1)->setHours(2, 0, 0),
            'backup_completed_at' => now()->subDays(1)->setHours(2, 5, 42),
            'verification_started_at' => now()->subDays(1)->setHours(2, 6, 0),
            'verification_completed_at' => now()->subDays(1)->setHours(2, 7, 15),
            'next_backup_at' => now()->addDay()->setHours(2, 0, 0),
            'notes' => 'Full backup completed successfully. Integrity verified.',
            'error_message' => null,
            'retention_days' => 30,
            'scheduled_delete_at' => now()->addDays(30),
        ]);

        // Successful incremental backup
        BackupMonitoring::create([
            'backup_name' => 'incremental_backup_2026_04_19_afternoon',
            'backup_type' => 'incremental',
            'status' => 'completed',
            'source_location' => '/var/lib/mysql/pageturner_db',
            'destination_location' => '/backups/s3://pageturner-backups/incremental_2026_04_19_14.tar.gz',
            'size_mb' => 45.75,
            'duration_seconds' => 52.30,
            'total_files' => 12,
            'total_tables' => 15,
            'compression_type' => 'gzip',
            'checksum' => 'sha256:f1e2d3c4b5a6z7y8x9w0v1u2t3s4r5q6p7o8n9m8',
            'is_encrypted' => true,
            'is_verified' => false,
            'backup_started_at' => now()->subHours(12)->setHours(14, 0, 0),
            'backup_completed_at' => now()->subHours(12)->setHours(14, 0, 52),
            'verification_started_at' => null,
            'verification_completed_at' => null,
            'next_backup_at' => now()->setHours(20, 0, 0),
            'notes' => 'Incremental backup completed. Verification pending.',
            'error_message' => null,
            'retention_days' => 7,
            'scheduled_delete_at' => now()->addDays(7),
        ]);

        // Failed backup
        BackupMonitoring::create([
            'backup_name' => 'full_backup_2026_04_18',
            'backup_type' => 'full',
            'status' => 'failed',
            'source_location' => '/var/lib/mysql/pageturner_db',
            'destination_location' => '/backups/s3://pageturner-backups/full_backup_2026_04_18.tar.gz',
            'size_mb' => null,
            'duration_seconds' => 125.00,
            'total_files' => null,
            'total_tables' => null,
            'compression_type' => null,
            'checksum' => null,
            'is_encrypted' => false,
            'is_verified' => false,
            'backup_started_at' => now()->subDays(2)->setHours(2, 0, 0),
            'backup_completed_at' => now()->subDays(2)->setHours(2, 2, 5),
            'verification_started_at' => null,
            'verification_completed_at' => null,
            'next_backup_at' => now()->addDay()->setHours(2, 0, 0),
            'notes' => 'Backup failed due to storage device unavailability.',
            'error_message' => 'Storage device not accessible: /backups/s3 mount point unreachable',
            'retention_days' => 30,
            'scheduled_delete_at' => null,
        ]);

        // Backup in progress
        BackupMonitoring::create([
            'backup_name' => 'full_backup_2026_04_19_running',
            'backup_type' => 'full',
            'status' => 'in_progress',
            'source_location' => '/var/lib/mysql/pageturner_db',
            'destination_location' => '/backups/s3://pageturner-backups/full_backup_2026_04_19_running.tar.gz',
            'size_mb' => 234.50,
            'duration_seconds' => null,
            'total_files' => null,
            'total_tables' => 15,
            'compression_type' => 'gzip',
            'checksum' => null,
            'is_encrypted' => true,
            'is_verified' => false,
            'backup_started_at' => now()->subMinutes(45),
            'backup_completed_at' => null,
            'verification_started_at' => null,
            'verification_completed_at' => null,
            'next_backup_at' => now()->addDays(2)->setHours(2, 0, 0),
            'notes' => 'Backup in progress - 52% complete',
            'error_message' => null,
            'retention_days' => 30,
            'scheduled_delete_at' => null,
        ]);

        // Corrupted backup detected
        BackupMonitoring::create([
            'backup_name' => 'full_backup_2026_04_17',
            'backup_type' => 'full',
            'status' => 'corrupted',
            'source_location' => '/var/lib/mysql/pageturner_db',
            'destination_location' => '/backups/s3://pageturner-backups/full_backup_2026_04_17.tar.gz',
            'size_mb' => 412.80,
            'duration_seconds' => 298.40,
            'total_files' => 125,
            'total_tables' => 15,
            'compression_type' => 'gzip',
            'checksum' => 'sha256:corrupted_checksum_mismatch',
            'is_encrypted' => true,
            'is_verified' => true,
            'backup_started_at' => now()->subDays(3)->setHours(2, 0, 0),
            'backup_completed_at' => now()->subDays(3)->setHours(2, 4, 58),
            'verification_started_at' => now()->subDays(2)->setHours(12, 0, 0),
            'verification_completed_at' => now()->subDays(2)->setHours(12, 15, 30),
            'next_backup_at' => now()->setHours(2, 0, 0),
            'notes' => 'ALERT: Backup integrity check failed. Checksum mismatch detected.',
            'error_message' => 'Verification failed: Checksum mismatch - expected a1b2c3d4... but got f1e2d3c4...',
            'retention_days' => 0,
            'scheduled_delete_at' => now(),
        ]);

        // Pending backup
        BackupMonitoring::create([
            'backup_name' => 'full_backup_2026_04_20_scheduled',
            'backup_type' => 'full',
            'status' => 'pending',
            'source_location' => '/var/lib/mysql/pageturner_db',
            'destination_location' => '/backups/s3://pageturner-backups/full_backup_2026_04_20.tar.gz',
            'size_mb' => null,
            'duration_seconds' => null,
            'total_files' => null,
            'total_tables' => null,
            'compression_type' => null,
            'checksum' => null,
            'is_encrypted' => false,
            'is_verified' => false,
            'backup_started_at' => now()->addDay()->setHours(2, 0, 0),
            'backup_completed_at' => null,
            'verification_started_at' => null,
            'verification_completed_at' => null,
            'next_backup_at' => now()->addDay()->setHours(2, 0, 0),
            'notes' => 'Backup scheduled for execution',
            'error_message' => null,
            'retention_days' => 30,
            'scheduled_delete_at' => null,
        ]);
    }
}
