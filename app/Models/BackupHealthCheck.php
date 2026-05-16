<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $backup_name
 * @property string $status (healthy|unhealthy)
 * @property int|null $age_days
 * @property int|null $storage_mb
 * @property string|null $issues
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BackupHealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_name',
        'status',
        'age_days',
        'storage_mb',
        'issues',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'issues' => 'array',
    ];

    /**
     * Scope to get healthy backups.
     */
    public function scopeHealthy($query)
    {
        return $query->where('status', 'healthy');
    }

    /**
     * Scope to get unhealthy backups.
     */
    public function scopeUnhealthy($query)
    {
        return $query->where('status', 'unhealthy');
    }

    /**
     * Check if backup is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Get human-readable storage size.
     */
    public function getFormattedStorageAttribute(): ?string
    {
        if (!$this->storage_mb) {
            return null;
        }

        if ($this->storage_mb < 1024) {
            return "{$this->storage_mb}MB";
        }

        $gb = $this->storage_mb / 1024;
        return number_format($gb, 2) . 'GB';
    }
};
