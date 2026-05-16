<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $audit_log_id
 * @property string $alert_type (email|slack|discord)
 * @property array $recipients
 * @property string $status (pending|sent|failed)
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AuditLogAlert extends Model
{
    protected $fillable = [
        'audit_log_id',
        'alert_type',
        'recipients',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the audit log associated with this alert.
     */
    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }

    /**
     * Scope to get pending alerts.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent alerts.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed alerts.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
};
