<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditLogAlert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Sensitive fields that should be excluded from audit logs
     */
    protected $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'remember_token',
        'stripe_id',
        'stripe_card_id',
        'payment_token',
        'credit_card',
        'cvv',
        'secret',
        'private_key',
        'access_token',
        'refresh_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Critical events that should trigger alerts
     */
    protected $criticalEvents = [
        'role_assigned',
        'permission_changed',
        '2fa_disabled',
        'password_changed',
        'admin_action',
        'security_event',
        'backup_started',
        'import_started',
        'export_started',
        'deleted',
    ];

    /**
     * Log an audit entry.
     */
    public function log(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        string $description = '',
        bool $isSensitive = false
    ): AuditLog {
        // Filter out sensitive fields
        $oldValues = $this->filterSensitiveFields($oldValues);
        $newValues = $this->filterSensitiveFields($newValues);

        // Determine level (critical or info)
        $level = in_array($event, $this->criticalEvents) ? 'critical' : 'info';
        if ($isSensitive) {
            $level = 'critical';
        }

        // Create audit log
        $auditLog = AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'entity_type' => $auditable ? strtolower(class_basename($auditable)) : null,
            'entity_id' => $auditable?->id,
            'action' => $event,
            'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
            'new_values' => !empty($newValues) ? json_encode($newValues) : null,
            'metadata' => json_encode($this->getMetadata()),
            'description' => $description,
            'level' => $level,
            'is_sensitive' => $isSensitive || in_array($event, $this->criticalEvents),
        ]);

        // Send alert if critical
        if ($level === 'critical') {
            $this->sendAlert($auditLog);
        }

        return $auditLog;
    }

    /**
     * Log authentication event.
     */
    public function logLogin($user, $success = true, $failureReason = null): AuditLog
    {
        return $this->log(
            event: $success ? 'login' : 'login_failed',
            description: $success
                ? "User {$user->email} logged in"
                : "Failed login attempt for {$user->email}" . ($failureReason ? ": {$failureReason}" : ''),
            isSensitive: !$success,
        );
    }

    /**
     * Log logout event.
     */
    public function logLogout($user): AuditLog
    {
        return $this->log(
            event: 'logout',
            description: "User {$user->email} logged out",
        );
    }

    /**
     * Log password change.
     */
    public function logPasswordChange($user): AuditLog
    {
        return $this->log(
            event: 'password_changed',
            auditable: $user,
            description: "User {$user->email} changed their password",
            isSensitive: true,
        );
    }

    /**
     * Log email verification.
     */
    public function logEmailVerified($user): AuditLog
    {
        return $this->log(
            event: 'email_verified',
            auditable: $user,
            description: "Email verified for {$user->email}",
        );
    }

    /**
     * Log 2FA enable.
     */
    public function log2FAEnabled($user): AuditLog
    {
        return $this->log(
            event: '2fa_enabled',
            auditable: $user,
            description: "Two-factor authentication enabled for {$user->email}",
            isSensitive: true,
        );
    }

    /**
     * Log 2FA disable.
     */
    public function log2FADisabled($user): AuditLog
    {
        return $this->log(
            event: '2fa_disabled',
            auditable: $user,
            description: "Two-factor authentication disabled for {$user->email}",
            isSensitive: true,
        );
    }

    /**
     * Log role assignment.
     */
    public function logRoleAssignment($user, $role, $previousRole = null): AuditLog
    {
        return $this->log(
            event: 'role_assigned',
            auditable: $user,
            oldValues: $previousRole ? ['role' => $previousRole] : [],
            newValues: ['role' => $role],
            description: "Role changed to {$role} for user {$user->email}",
            isSensitive: true,
        );
    }

    /**
     * Log permission change.
     */
    public function logPermissionChange($user, $permission, $action): AuditLog
    {
        return $this->log(
            event: 'permission_changed',
            auditable: $user,
            description: "Permission '{$permission}' {$action} for user {$user->email}",
            isSensitive: true,
        );
    }

    /**
     * Log data modification (CRUD).
     */
    public function logDataModification(
        string $action,
        Model $model,
        array $oldValues = [],
        array $newValues = [],
        string $description = ''
    ): AuditLog {
        return $this->log(
            event: $action, // 'created', 'updated', 'deleted', 'restored'
            auditable: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            description: $description,
        );
    }

    /**
     * Log import operation.
     */
    public function logImport($moduleName, $totalRecords, $successfulRecords, $failedRecords): AuditLog
    {
        return $this->log(
            event: 'import_completed',
            description: "{$moduleName} import: {$successfulRecords}/{$totalRecords} successful, {$failedRecords} failed",
            isSensitive: false,
        );
    }

    /**
     * Log export operation.
     */
    public function logExport($moduleName, $totalRecords, $format): AuditLog
    {
        return $this->log(
            event: 'export_completed',
            description: "{$moduleName} export to {$format}: {$totalRecords} records",
            isSensitive: false,
        );
    }

    /**
     * Log backup operation.
     */
    public function logBackup($status, $fileName = null, $size = null, $error = null): AuditLog
    {
        $event = $status === 'completed' ? 'backup_completed' : 'backup_failed';
        $description = "Database backup {$status}";

        if ($fileName) {
            $description .= ": {$fileName}";
        }
        if ($size) {
            $description .= " ({$size}MB)";
        }
        if ($error) {
            $description .= " - Error: {$error}";
        }

        return $this->log(
            event: $event,
            description: $description,
            isSensitive: false,
        );
    }

    /**
     * Get request metadata.
     */
    protected function getMetadata(): array
    {
        return [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::url(),
            'method' => Request::method(),
            'referer' => Request::header('referer'),
        ];
    }

    /**
     * Filter out sensitive fields from arrays.
     */
    protected function filterSensitiveFields(array $data): array
    {
        return array_filter($data, function ($key) {
            return !in_array(strtolower($key), array_map('strtolower', $this->sensitiveFields));
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Send alert for critical events.
     */
    protected function sendAlert(AuditLog $auditLog): void
    {
        $adminEmail = config('audit.alert_email', env('AUDIT_ALERT_EMAIL', 'admin@example.com'));

        AuditLogAlert::create([
            'audit_log_id' => $auditLog->id,
            'alert_type' => 'email',
            'recipients' => [$adminEmail],
            'status' => 'pending',
        ]);

        // Additional alerts could be sent to Slack, Discord, etc.
        // For now, we'll dispatch a job to send the email asynchronously
    }

    /**
     * Archive old logs (older than 1 year).
     */
    public function archiveOldLogs(): int
    {
        $cutoffDate = now()->subYear();

        $archived = AuditLog::where('created_at', '<', $cutoffDate)
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        return $archived;
    }

    /**
     * Get audit trail for a specific model.
     */
    public function getAuditTrail(Model $model, $limit = 50)
    {
        return AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->id)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Export audit logs to array (for CSV/PDF).
     */
    public function exportLogs($filters = [], $format = 'csv'): array
    {
        $query = AuditLog::query();

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['event'])) {
            $query->byEvent($filters['event']);
        }

        if (isset($filters['level'])) {
            $query->byLevel($filters['level']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        $logs = $query->with('user')->latest()->get();

        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'ID' => $log->id,
                'UUID' => $log->uuid,
                'User' => $log->user?->email ?? 'System',
                'Event' => $log->event_label,
                'Model' => $log->auditable_type ? class_basename($log->auditable_type) : '-',
                'Model ID' => $log->auditable_id ?? '-',
                'Level' => ucfirst($log->level),
                'IP Address' => (json_decode($log->metadata ?? '[]', true)['ip_address'] ?? '-'),
                'URL' => (json_decode($log->metadata ?? '[]', true)['url'] ?? '-'),
                'Description' => $log->description,
                'Timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                'Checksum Valid' => $log->verifyChecksum() ? 'Yes' : 'No (Tampered)',
            ];
        }

        return $data;
    }
};
