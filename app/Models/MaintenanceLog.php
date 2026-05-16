<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $task_name
 * @property string $status (pending|running|completed|failed)
 * @property string|null $command
 * @property string|null $output
 * @property string|null $error_message
 * @property int|null $duration_seconds
 * @property bool $was_manual
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_name',
        'status',
        'command',
        'output',
        'error_message',
        'duration_seconds',
        'was_manual',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'was_manual' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Scope to get successful maintenance tasks.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed maintenance tasks.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter by task name.
     */
    public function scopeForTask($query, $taskName)
    {
        return $query->where('task_name', $taskName);
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
};
