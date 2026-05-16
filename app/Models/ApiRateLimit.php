<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiRateLimit extends Model
{
    use HasFactory;

    protected $table = 'api_rate_limits';

    protected $fillable = [
        'user_id',
        'ip_address',
        'endpoint',
        'method',
        'requests_count',
        'limit',
        'remaining',
        'reset_at_timestamp',
        'is_throttled',
        'status',
        'reason',
        'throttled_until',
    ];

    protected $casts = [
        'throttled_until' => 'datetime',
        'is_throttled' => 'boolean',
    ];

    public function getThrottledAtAttribute()
    {
        return $this->throttled_until ?? ($this->is_throttled ? $this->created_at : null);
    }

    /**
     * Get the user associated with rate limit.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if rate limit is currently active.
     */
    public function isActive()
    {
        if (!$this->throttled_until) {
            return false;
        }
        return now()->lessThan($this->throttled_until);
    }

    /**
     * Get seconds until reset.
     */
    public function getSecondsUntilResetAttribute()
    {
        if (!$this->reset_at_timestamp) {
            return null;
        }
        $seconds = $this->reset_at_timestamp - time();
        return max(0, $seconds);
    }

    /**
     * Get percentage of limit used.
     */
    public function getUsagePercentageAttribute()
    {
        if (!$this->limit || $this->limit === 0) {
            return 0;
        }
        return round(($this->requests_count / $this->limit) * 100, 2);
    }
}
