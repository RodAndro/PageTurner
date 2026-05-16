# Audit Log System - Setup & Usage Guide

## Overview

The Audit Log System is a comprehensive security and compliance solution for tracking all user activities and system operations. It provides tamper detection, integrity verification, and real-time alerts for critical events.

## Features

- **Complete Activity Tracking**: Log all user actions including login, logout, data modifications, role changes, etc.
- **Integrity Verification**: Checksums ensure audit logs cannot be tampered with undetected
- **Sensitive Data Protection**: Automatically redact passwords, tokens, and other sensitive fields
- **Critical Event Alerts**: Real-time notifications for critical security events
- **Advanced Searching**: Filter logs by user, event type, date range, and more
- **Export Capabilities**: Export audit logs to CSV or PDF for compliance
- **Log Archiving**: Automatically archive old logs while maintaining searchability
- **Saved Searches**: Save common filter combinations for quick access

## Database Setup

The audit log system requires four main tables:

### 1. `audit_logs` - Main audit log records
Stores comprehensive information about each audited event.

### 2. `audit_log_searches` - Saved search filters
Stores user-defined search configurations for quick access.

### 3. `audit_log_alerts` - Alert tracking
Tracks which logs triggered alerts and their delivery status.

### 4. `audit_log_checksums` - Integrity verification
Stores checksums for verifying log integrity.

Run the migration:
```bash
php artisan migrate
```

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Audit Log Alert Configuration
AUDIT_ALERT_EMAIL=admin@example.com
AUDIT_LOG_RETENTION_DAYS=365
AUDIT_ENABLE_ALERTS=true
AUDIT_ALERT_CHANNELS=email,slack,discord  # Comma-separated
```

### Configuration File

Create `config/audit.php` (optional):

```php
return [
    'alert_email' => env('AUDIT_ALERT_EMAIL', 'admin@example.com'),
    'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
    'enable_alerts' => env('AUDIT_ENABLE_ALERTS', true),
    'alert_channels' => explode(',', env('AUDIT_ALERT_CHANNELS', 'email')),
    'sensitive_fields' => [
        'password', 'password_confirmation', 'token', 'api_token',
        'stripe_id', 'cvv', 'secret', 'private_key',
    ],
    'critical_events' => [
        'role_assigned', 'permission_changed', '2fa_disabled',
        'password_changed', 'admin_action', 'security_event',
    ],
];
```

## Service Layer - AuditLogService

The `AuditLogService` is the core of the system. It provides:

### Basic Logging

```php
use App\Services\AuditLogService;

class YourController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {}

    public function update(Request $request, $model)
    {
        $oldValues = $model->toArray();
        $model->update($request->validated());
        
        $this->auditLogService->logDataModification(
            action: 'updated',
            model: $model,
            oldValues: $oldValues,
            newValues: $model->toArray(),
            description: "Updated " . class_basename($model)
        );
    }
}
```

### Authentication Events

The system automatically logs:
- User login (success/failure)
- User logout
- Password changes
- Email verification
- 2FA enable/disable

### Custom Events

```php
// Log custom business events
$this->auditLogService->log(
    event: 'order_refunded',
    auditable: $order,
    oldValues: ['status' => 'completed'],
    newValues: ['status' => 'refunded'],
    description: "Order #" . $order->id . " refunded to customer",
);
```

### Role and Permission Changes

```php
$this->auditLogService->logRoleAssignment(
    user: $user,
    role: 'moderator',
    previousRole: 'user'
);

$this->auditLogService->logPermissionChange(
    user: $user,
    permission: 'delete_posts',
    action: 'granted'
);
```

### Import/Export Operations

```php
$this->auditLogService->logImport(
    moduleName: 'Products',
    totalRecords: 1000,
    successfulRecords: 995,
    failedRecords: 5
);

$this->auditLogService->logExport(
    moduleName: 'Orders',
    totalRecords: 500,
    format: 'csv'
);
```

### Backup Operations

```php
$this->auditLogService->logBackup(
    status: 'completed',
    fileName: 'backup_2024_01_15.sql',
    size: 256
);
```

## Models

### AuditLog Model

The main audit log record with the following key properties:

```php
$log = AuditLog::find(1);

// Accessors
$log->event_label          // Humanized event name
$log->level                // 'info', 'warning', 'critical'
$log->is_sensitive         // True if sensitive operation
$log->uuid                 // Unique identifier
$log->user                 // Related user (if applicable)
$log->auditable            // Related model (if applicable)
$log->alerts               // Related alerts
$log->metadata             // Request metadata (IP, URL, etc.)
$log->created_at           // Timestamp
$log->archived_at          // Archive timestamp

// Methods
$log->verifyChecksum()     // Verify integrity
```

### Querying

```php
// Filter by user
AuditLog::byUser($userId)->get();

// Filter by event
AuditLog::byEvent('login')->get();

// Filter by level
AuditLog::critical()->get();
AuditLog::warning()->get();

// Filter by date range
AuditLog::dateRange('2024-01-01', '2024-01-31')->get();

// Search in description
AuditLog::search('refund')->get();

// Get online logs (not archived)
AuditLog::online()->get();

// Get archived logs
AuditLog::archived()->get();

// Get sensitive operations
AuditLog::sensitive()->get();
```

## Dashboard

Access the audit log dashboard at: `/admin/audit-logs/dashboard`

### Dashboard Features

- **Statistics**: Total logs, critical events, sensitive operations, pending alerts
- **Event Distribution Chart**: Visual breakdown of event types
- **User Activity**: Top 5 most active users
- **Recent Logs**: Last 10 audit log entries with drill-down capability

## Audit Log Viewer

Access at: `/admin/audit-logs`

### Features

- **Advanced Filtering**: Filter by user, event, level, date range
- **Full-Text Search**: Search descriptions and metadata
- **Saved Searches**: Save and reuse filter combinations
- **Export**: Export filtered logs to CSV or PDF
- **Detailed View**: Click any log to see complete details including:
  - Before/after values for changes
  - Request metadata (IP, user agent, URL)
  - Related logs for the same model
  - Integrity status and checksum

## Event Listeners

The system includes listeners for Laravel authentication events:

### Automatic Logging

The following events are automatically logged via event listeners:

```
- Illuminate\Auth\Events\Login       → LogLogin listener
- Illuminate\Auth\Events\Logout      → LogLogout listener
- Illuminate\Auth\Events\Failed      → LogFailedLogin listener
```

These listeners are registered in `app/Providers/EventServiceProvider.php`.

## Integrity Verification

Each audit log includes a SHA-256 checksum computed from:
- User ID
- Event
- Timestamp
- Description
- Old values
- New values

### Verify Integrity

```php
$log = AuditLog::find(1);

if ($log->verifyChecksum()) {
    echo "Log is valid - not tampered with";
} else {
    echo "⚠️ Log appears tampered!";
}
```

### Batch Verification

Dashboard: `/admin/audit-logs/dashboard` → "Verify Integrity" button

This checks all online logs and alerts if tampering is detected.

## Archiving

### Automatic Archiving

Logs older than the configured retention period are automatically marked for archival.

### Manual Archiving

API endpoint: `POST /admin/audit-logs/archive`

```php
$archived = $this->auditLogService->archiveOldLogs();
// Returns number of archived records
```

### Archived Logs

View archived logs at: `/admin/audit-logs/archived`

Archived logs are searchable but visually separated from active logs.

## Alerts

### How Alerts Work

1. Critical events are automatically identified based on the event type
2. Alert records are created with 'pending' status
3. Alert jobs are dispatched to send notifications (asynchronously)
4. Alerts can be sent via multiple channels:
   - Email
   - Slack
   - Discord
   - Custom webhooks

### Alert Configuration

```php
// In AuditLogService
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
```

### Email Alerts

Configure the alert email:

```env
AUDIT_ALERT_EMAIL=security-team@yoursite.com
```

## Export & Compliance

### Export Formats

- **CSV**: Standard comma-separated values, Excel compatible
- **PDF**: Formatted report (requires barryvdh/laravel-dompdf package)

### Exported Data

Each exported log includes:
- ID and UUID
- User email
- Event name
- Model type and ID
- Severity level
- IP address and URL
- Description
- Timestamp
- Checksum validity status

### Compliance

The audit log system helps meet compliance requirements for:
- GDPR (data access logs)
- HIPAA (security event logs)
- SOC 2 (activity tracking)
- ISO 27001 (security monitoring)

## Testing

### Unit Tests

```php
public function test_audit_log_creation()
{
    $auditLog = AuditLog::factory()->create();
    $this->assertNotNull($auditLog->checksum);
}

public function test_checksum_verification()
{
    $log = AuditLog::factory()->create();
    $this->assertTrue($log->verifyChecksum());
}

public function test_sensitive_fields_filtered()
{
    // Service should remove password fields
    // Verify in exported data
}
```

### Integration Tests

```php
public function test_login_creates_audit_log()
{
    $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password'
    ]);
    
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'login'
    ]);
}
```

## Troubleshooting

### No logs appearing

1. Verify EventServiceProvider is registered in `bootstrap/providers.php`
2. Check that migrations have run: `php artisan migrate`
3. Enable debug mode to see any errors
4. Verify user is authenticated (auto logs don't appear for guests)

### Tampered logs detected

1. Check database for unauthorized modifications
2. Review access logs
3. Verify database backups are intact
4. Consider it a security incident

### Performance issues

1. Archive old logs regularly
2. Add index on frequently searched columns:
   ```sql
   CREATE INDEX audit_logs_user_id_idx ON audit_logs(user_id);
   CREATE INDEX audit_logs_event_idx ON audit_logs(event);
   CREATE INDEX audit_logs_created_at_idx ON audit_logs(created_at);
   ```
3. Consider data retention policy

### Alerts not sending

1. Verify AUDIT_ALERT_EMAIL is configured
2. Check mail configuration in config/mail.php
3. Review queue status if using async jobs
4. Check application logs for errors

## Best Practices

1. **Regular Reviews**: Review audit logs regularly for security events
2. **Automated Archiving**: Set up scheduled command to archive old logs
3. **Access Control**: Restrict audit log access to authorized admins only
4. **Backup**: Include audit logs in your backup strategy
5. **Monitoring**: Set up alerts for critical security events
6. **Documentation**: Document business event logging in your code
7. **Testing**: Write tests for audit log creation in new features
8. **Compliance**: Maintain audit logs according to your compliance requirements

## API Reference

### AuditLogService Methods

```php
// Core logging
log(string $event, ?Model $auditable, array $oldValues, array $newValues, string $description, bool $isSensitive)

// Authentication
logLogin($user, $success = true, $failureReason = null)
logLogout($user)
logPasswordChange($user)
logEmailVerified($user)

// 2FA
log2FAEnabled($user)
log2FADisabled($user)

// Permissions
logRoleAssignment($user, $role, $previousRole = null)
logPermissionChange($user, $permission, $action)

// Data operations
logDataModification(string $action, Model $model, array $oldValues, array $newValues, string $description)

// Operations
logImport($moduleName, $totalRecords, $successfulRecords, $failedRecords)
logExport($moduleName, $totalRecords, $format)
logBackup($status, $fileName = null, $size = null, $error = null)

// Maintenance
archiveOldLogs()
getAuditTrail(Model $model, $limit = 50)
exportLogs($filters = [], $format = 'csv')
```

## Support

For issues or questions, contact your system administrator or security team.
