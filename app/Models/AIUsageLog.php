<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AIUsageLog extends Model
{
    use SoftDeletes;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'user_id',
        'feature',
        'provider',
        'model',
        'tokens_used',
        'cost_estimate',
        'success',
        'latency_ms',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'success' => 'boolean',
            'cost_estimate' => 'decimal:6',
        ];
    }
}
