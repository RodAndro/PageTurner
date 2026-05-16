<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type (manual|scheduled|health-check)
 * @property string $status (pending|processing|completed|failed)
 * @property string|null $backup_name
 * @property string|null $backup_file_path
 * @property int|null $file_size_mb
 * @property int|null $duration_seconds
 * @property int|null $total_rows
 * @property string|null $error_message
 * @property array|null $details
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'backup_name',
        'backup_file_path',
        'file_size_mb',
        'duration_seconds',
        'total_rows',
        'error_message',
        'details',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that triggered this backup.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get successful backups.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed backups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get recent backups.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get human-readable duration.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        if ($this->duration_seconds < 60) {
            return "{$this->duration_seconds}s";
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size_mb) {
            return null;
        }

        if ($this->file_size_mb < 1024) {
            return "{$this->file_size_mb}MB";
        }

        $gb = $this->file_size_mb / 1024;
        return number_format($gb, 2) . 'GB';
    }
};
