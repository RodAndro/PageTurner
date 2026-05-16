<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AIConversation extends Model
{
    use SoftDeletes;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'provider',
        'status',
        'prompt',
        'response',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'metadata' => 'array',
        ];
    }
}
