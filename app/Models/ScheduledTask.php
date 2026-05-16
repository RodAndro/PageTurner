<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_name',
        'description',
        'task_type',
        'cron_expression',
        'status',
        'last_status',
        'last_run_at',
        'last_success_at',
        'last_failed_at',
        'run_count',
        'failure_count',
        'duration_ms',
        'last_error_message',
        'consecutive_failures',
        'next_run_at',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Check if task is enabled.
     */
    public function isEnabled()
    {
        return $this->status === 'enabled';
    }

    /**
     * Check if task is running.
     */
    public function isRunning()
    {
        return $this->last_status === 'running';
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRateAttribute()
    {
        if ($this->run_count === 0) {
            return 100;
        }
        $successes = $this->run_count - $this->failure_count;
        return round(($successes / $this->run_count) * 100, 2);
    }

    /**
     * Get health status based on consecutive failures.
     */
    public function getHealthStatusAttribute()
    {
        if ($this->consecutive_failures === 0) {
            return 'healthy';
        } elseif ($this->consecutive_failures < 3) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
}
