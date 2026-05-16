<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array $filters
 * @property string|null $query
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AuditLogSearch extends Model
{
    protected $table = 'audit_log_searches';

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'query',
        'is_public',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the user associated with this saved search.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get public searches.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get searches by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
};
