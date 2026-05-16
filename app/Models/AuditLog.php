<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property string $event (created|updated|deleted|login|logout|etc.)
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $metadata
 * @property string|null $description
 * @property string $level (info|warning|critical)
 * @property string|null $checksum
 * @property bool $is_sensitive
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'metadata',
        'description',
        'level',
        'checksum',
        'is_sensitive',
        'archived_at',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate UUID if not set
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }

            $model->action ??= $model->event;
            $model->entity_type ??= $model->auditable_type ? Str::of(class_basename($model->auditable_type))->snake()->toString() : null;
            $model->entity_id ??= $model->auditable_id;
            $model->level ??= 'info';
            $model->old_values = $model->normalizeJsonValue($model->old_values);
            $model->new_values = $model->normalizeJsonValue($model->new_values);
            $model->metadata = $model->normalizeJsonValue($model->metadata);
            $model->checksum ??= $model->calculateBasicChecksum();
        });

        static::created(function ($model) {
            if ($model->entity_type && $model->entity_id) {
                $model->checksum = $model->calculateAuditTrailChecksum();
                $model->saveQuietly();
            }
        });
    }

    /**
     * Get the user associated with this audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get alerts for this audit log.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AuditLogAlert::class);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    /**
     * Scope to filter by auditable model.
     */
    public function scopeByAuditable($query, $type, $id = null)
    {
        $query->where('auditable_type', $type);

        if ($id) {
            $query->where('auditable_id', $id);
        }

        return $query;
    }

    /**
     * Scope to filter by level (info, warning, critical).
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for critical events only.
     */
    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get sensitive operations only.
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope to get non-archived logs (1 year or less).
     */
    public function scopeOnline($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to get archived logs.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Search logs by description or user email.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereFullText('description', $term)
                ->orWhereHas('user', function ($u) use ($term) {
                    $u->where('email', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Calculate SHA256 checksum for tamper detection.
     */
    private function calculateChecksum(): string
    {
        $data = [
            'user_id' => $this->user_id,
            'event' => $this->event,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
        ];

        return hash('sha256', json_encode($data));
    }

    public function calculateBasicChecksum(): string
    {
        $data = json_encode([
            'event' => $this->event,
            'user_id' => $this->user_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
        ]);

        return hash('sha256', $data);
    }

    public function calculateAuditTrailChecksum(): string
    {
        return hash('sha256',
            $this->entity_type .
            $this->entity_id .
            $this->event .
            $this->old_values .
            $this->new_values .
            $this->created_at->toDateTimeString()
        );
    }

    protected function normalizeJsonValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : json_encode($value);
    }

    /**
     * Verify checksum for tamper detection.
     */
    public function verifyChecksum(): bool
    {
        if ($this->entity_type && $this->entity_id) {
            return $this->checksum === $this->calculateAuditTrailChecksum();
        }

        return $this->checksum === $this->calculateBasicChecksum()
            || $this->checksum === $this->calculateChecksum();
    }

    /**
     * Get human-readable event label.
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'login' => 'Login',
            'logout' => 'Logout',
            'login_failed' => 'Login Failed',
            'password_changed' => 'Password Changed',
            'email_verified' => 'Email Verified',
            'email_verification_sent' => 'Verification Sent',
            '2fa_enabled' => '2FA Enabled',
            '2fa_disabled' => '2FA Disabled',
            'role_assigned' => 'Role Assigned',
            'permission_changed' => 'Permission Changed',
            'import_started' => 'Import Started',
            'import_completed' => 'Import Completed',
            'export_started' => 'Export Started',
            'export_completed' => 'Export Completed',
            'backup_started' => 'Backup Started',
            'backup_completed' => 'Backup Completed',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }

    /**
     * Get display label for level.
     */
    public function getLevelBadgeAttribute(): string
    {
        return match ($this->level) {
            'critical' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Critical</span>',
            'warning' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Warning</span>',
            'info' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Info</span>',
            default => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>',
        };
    }

    /**
     * Get the auditable model instance.
     */
    public function getAuditableAttribute()
    {
        if ($this->auditable_type && class_exists($this->auditable_type)) {
            return $this->auditable_type::find($this->auditable_id);
        }
        return null;
    }

    /**
     * Get changes as diff array.
     */
    public function getChangesAttribute(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;

                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }
};
