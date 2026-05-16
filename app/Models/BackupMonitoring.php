<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BackupMonitoring extends Model
{
    use HasFactory;

    protected $table = 'backup_monitoring';

    protected $fillable = [
        'backup_name',
        'backup_type',
        'status',
        'source_location',
        'destination_location',
        'size_mb',
        'duration_seconds',
        'total_files',
        'total_tables',
        'compression_type',
        'checksum',
        'is_encrypted',
        'is_verified',
        'backup_started_at',
        'backup_completed_at',
        'verification_started_at',
        'verification_completed_at',
        'next_backup_at',
        'notes',
        'error_message',
        'retention_days',
        'scheduled_delete_at',
        'file_path',
        'started_at',
    ];

    protected $casts = [
        'size_mb' => 'decimal:2',
        'duration_seconds' => 'decimal:2',
        'is_encrypted' => 'boolean',
        'is_verified' => 'boolean',
        'backup_started_at' => 'datetime',
        'backup_completed_at' => 'datetime',
        'verification_started_at' => 'datetime',
        'verification_completed_at' => 'datetime',
        'next_backup_at' => 'datetime',
        'scheduled_delete_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (BackupMonitoring $backup): void {
            $backup->backup_type = match ($backup->backup_type) {
                'database' => 'full',
                default => $backup->backup_type ?: 'full',
            };

            $backup->backup_started_at ??= $backup->started_at ?? now();
            $backup->source_location ??= database_path();
            $backup->destination_location ??= $backup->file_path ?? ('backups/' . $backup->backup_name . '.zip');
            $backup->retention_days ??= 30;
        });
    }

    public function getFilePathAttribute(): ?string
    {
        return $this->attributes['destination_location'] ?? null;
    }

    public function setFilePathAttribute(?string $value): void
    {
        $this->attributes['destination_location'] = $value;
    }

    public function setStartedAtAttribute($value): void
    {
        $this->attributes['backup_started_at'] = $value;
    }

    /**
     * Check if backup is healthy.
     */
    public function isHealthy()
    {
        return $this->status === 'verified' && !$this->is_corrupted;
    }

    /**
     * Get backup status for display.
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'completed' => 'success',
            'verified' => 'success',
            'failed' => 'danger',
            'corrupted' => 'danger',
            'in_progress' => 'warning',
            'pending' => 'info',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    /**
     * Check if backup should be retained.
     */
    public function isRetained()
    {
        if (!$this->backup_completed_at) {
            return false;
        }
        $expirationDate = $this->backup_completed_at->addDays($this->retention_days);
        return now()->lessThan($expirationDate);
    }

    /**
     * Get days until deletion.
     */
    public function getDaysUntilDeletionAttribute()
    {
        if (!$this->scheduled_delete_at) {
            return null;
        }
        return now()->diffInDays($this->scheduled_delete_at, false);
    }

    /**
     * Get backup age in hours.
     */
    public function getBackupAgeHoursAttribute()
    {
        return $this->backup_completed_at 
            ? $this->backup_completed_at->diffInHours(now())
            : null;
    }
}
