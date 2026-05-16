# Backup & Maintenance System Documentation

## Overview

The PageTurner Backup & Maintenance System provides automated daily backups, retention management, health checks, and scheduled maintenance tasks to ensure system reliability and data protection.

## Components

### 1. Backup System

#### Schedule
- **Daily Backups**: 02:00 AM (UTC)
- **Weekly Full Backups**: Every Sunday
- **Backup Window**: 1 hour

#### Retention Policy
- **Keep all backups**: 7 days
- **Keep daily backups**: 14 additional days (total 21 days)
- **Keep weekly backups**: 4 weeks
- **Keep monthly backups**: 12 months
- **Keep yearly backups**: 2 years
- **Storage limit**: 50GB

#### Backup Contents
- Database dump (SQL)
- Application files (app, bootstrap, config, resources, routes)
- Configuration files (.env)
- Uploaded files (book covers, user profiles)
- System logs

#### Backup Locations
- **Local Storage**: `storage/backups/` (encrypted with AES-256)
- **S3 Cloud Storage**: AWS S3 or S3-compatible service

#### File Encryption
- Algorithm: AES-256 encryption
- Password: Set via `BACKUP_ARCHIVE_PASSWORD` environment variable
- Format: ZIP archive

### 2. Maintenance Scheduler

#### Scheduled Tasks

| Task | Frequency | Time | Description |
|------|-----------|------|-------------|
| `backup:run` | Daily | 02:00 | Full database and files backup |
| `backup:clean` | Daily | 03:00 | Remove old backups per retention policy |
| `order:cleanup-pending` | Hourly | Every hour | Cancel pending orders > 24 hours old |
| `session:cleanup` | Daily | 04:00 | Clear expired sessions from database |
| `log:rotate` | Weekly | Monday 05:00 | Archive and compress log files older than 30 days |
| `report:generate-daily` | Daily | 06:00 | Generate daily sales report (JSON) |
| `notification:prune` | Weekly | Sunday 07:00 | Delete notifications older than 90 days |
| `audit:archive` | Monthly | 1st of month, 01:00 | Archive audit logs older than 12 months |

#### Task Features
- **withoutOverlapping()**: Prevents overlapping task execution
- **onSuccess()**: Logs success to maintenance_logs table
- **onFailure()**: Logs failure and sends email alerts
- **Automatic Retry**: Failed tasks can be manually re-triggered

### 3. Monitoring & Health Checks

#### Health Check Criteria
- **Maximum Age**: Backup must be less than 1 day old
- **Maximum Storage**: Total backup storage must be under 50GB
- **Frequency**: Checked daily

#### Health Statuses
- **Healthy**: All criteria met
- **Unhealthy**: One or more criteria failed

#### Notifications
- **Backup Failure**: Email alert to admin
- **Unhealthy Backup**: Email alert to admin
- **Cleanup Failure**: Email alert to admin
- **Successful Backup**: No notification (logs only)

### 4. Admin Dashboard

#### Features
- Statistics dashboard (total/successful/failed backups)
- Recent backups list with status
- Failed backups list with error details
- Backup health status
- Recent maintenance task history
- Manual backup trigger
- Task execution logs

#### Access
- URL: `/admin/backup-maintenance`
- Required Role: Admin
- Required Auth: Verified email

## Database Tables

### backup_logs
Tracks all backup operations.

```sql
- id (PK)
- user_id (FK to users, nullable)
- type (manual|scheduled|health-check)
- status (pending|processing|completed|failed)
- backup_name
- backup_file_path
- file_size_mb
- duration_seconds
- total_rows
- error_message
- details (JSON)
- started_at
- completed_at
- created_at, updated_at
```

### maintenance_logs
Tracks all scheduled maintenance task executions.

```sql
- id (PK)
- task_name (backup:run, session:cleanup, etc.)
- status (pending|running|completed|failed)
- command
- output
- error_message
- duration_seconds
- was_manual (boolean)
- started_at
- completed_at
- created_at, updated_at
```

### backup_health_checks
Stores backup health check results.

```sql
- id (PK)
- backup_name
- status (healthy|unhealthy)
- age_days
- storage_mb
- issues (JSON array)
- last_checked_at
- created_at, updated_at
```

## Configuration

### Environment Variables

```env
# Backup encryption password
BACKUP_ARCHIVE_PASSWORD=your-secure-password

# Notification email
BACKUP_NOTIFICATION_EMAIL=admin@example.com

# Slack webhook (optional)
BACKUP_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK

# Discord webhook (optional)
BACKUP_DISCORD_WEBHOOK_URL=https://discordapp.com/api/webhooks/YOUR/WEBHOOK

# S3 configuration (optional)
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pageturner-backups
```

### Configure in `config/backup.php`
```php
'backup' => [
    'name' => env('APP_NAME', 'PageTurner'),
    'source' => [
        'files' => [
            'include' => [
                base_path('app'),
                base_path('config'),
                storage_path('app/book-covers'),
                // ...
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
                // ...
            ],
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'disks' => ['local', 's3'], // Multiple destinations
    ],
    'password' => env('BACKUP_ARCHIVE_PASSWORD'),
    'encryption' => 'default', // AES-256
],

'monitor_backups' => [
    [
        'name' => env('APP_NAME'),
        'disks' => ['local', 's3'],
        'health_checks' => [
            MaximumAgeInDays::class => 1,
            MaximumStorageInMegabytes::class => 51200, // 50GB
        ],
    ],
],

'cleanup' => [
    'keep_all_backups_for_days' => 7,
    'keep_daily_backups_for_days' => 14,
    'keep_weekly_backups_for_weeks' => 4,
    'keep_monthly_backups_for_months' => 12,
    'keep_yearly_backups_for_years' => 2,
    'delete_oldest_backups_when_using_more_megabytes_than' => 51200,
],
```

## API Routes

### Backup Routes
```
POST   /admin/backup-maintenance/backups/trigger          - Trigger manual backup
GET    /admin/backup-maintenance/backups                  - List all backups
GET    /admin/backup-maintenance/backups/{backupId}       - View backup details
GET    /admin/backup-maintenance/backups/{backupId}/download - Download backup
DELETE /admin/backup-maintenance/backups/{backupId}       - Delete backup
```

### Maintenance Routes
```
GET    /admin/backup-maintenance/tasks                    - List all tasks
GET    /admin/backup-maintenance/tasks/{taskId}           - View task details
POST   /admin/backup-maintenance/tasks/{taskName}/run     - Run task manually
```

### Dashboard
```
GET    /admin/backup-maintenance                          - Main dashboard
```

## Manual Operations

### Trigger Manual Backup
1. Navigate to Admin Dashboard → Backup & Maintenance
2. Click "Trigger Backup Now" button
3. Wait for completion (typically 5-15 minutes)
4. Check status dashboard for results

### Run Maintenance Task
1. Go to Maintenance Tasks list
2. Find desired task
3. Click "Run Now" button
4. Confirm action
5. View logs for execution details

### View Backup Details
1. Navigate to Backups list
2. Click on backup name or "View" button
3. Review backup metadata:
   - Backup name and file path
   - File size
   - Duration
   - Error details (if failed)
   - Disk locations

### Download Backup
1. Go to Backups list
2. Find completed backup
3. Click "Download" button
4. Encrypted ZIP file will download

### Delete Backup
1. Navigate to Backups list
2. Click "Delete" button
3. Confirm deletion
4. Backup record and file removed

## Cron Job Configuration

Add to server crontab for automatic task scheduling:

```bash
* * * * * cd /path/to/pageturner && php artisan schedule:run >> /dev/null 2>&1
```

This command runs every minute and checks if any scheduled tasks are due for execution.

### Verify Scheduler is Running
```bash
# Check Laravel scheduler logs
tail -f storage/logs/laravel.log | grep schedule

# Test scheduler manually
php artisan schedule:work # Runs scheduler in foreground

# Run specific command
php artisan backup:run
php artisan session:cleanup
```

## Error Handling

### Backup Failures
1. Check `backup_logs` table for error message
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify:
   - Sufficient disk space
   - Database connectivity
   - File permissions on backup directories
   - S3 credentials (if using cloud storage)

### Task Execution Failures
1. Review `maintenance_logs` table
2. Check task output/error message
3. Common issues:
   - Database connection timeouts
   - File system permissions
   - Memory limits (increase in php.ini)

### Health Check Failures
1. Review `backup_health_checks` table
2. Check if:
   - Backup age exceeds 1 day
   - Storage usage exceeds 50GB limit
3. Actions:
   - Run `backup:clean` manually
   - Review retention settings

## Notifications

### Email Alerts
Recipients: Email configured in `BACKUP_NOTIFICATION_EMAIL`

**Trigger Events:**
- Backup fails
- Unhealthy backup detected
- Cleanup fails

**Content:**
- Error message
- Timestamp
- Backup/task name
- Recommended actions

### Slack Integration
Set `BACKUP_SLACK_WEBHOOK_URL` to enable Slack notifications.

### Discord Integration
Set `BACKUP_DISCORD_WEBHOOK_URL` to enable Discord notifications.

## Performance Considerations

### Backup Size Optimization
- Database uses SQL compression
- Files compressed with ZIP (default algorithm)
- Older backups moved to archive storage

### Large Database Handling
- Backups >10,000 rows automatically queued
- Chunked reading (1,000 rows per chunk)
- Batch database writes for efficiency

### Storage Management
- Old backups automatically pruned per retention policy
- Oldest backups deleted when storage exceeds 50GB
- Monthly archiving of logs reduces storage

## Troubleshooting

### "Backup failed: Permission denied"
```bash
# Fix permissions
sudo chown -R www-data:www-data /path/to/pageturner/storage
chmod -R 755 /path/to/pageturner/storage
```

### "Backup not running on schedule"
```bash
# Verify cron job
crontab -l | grep "schedule:run"

# Test scheduler
php artisan schedule:work

# Check logs
tail -f storage/logs/laravel.log
```

### "S3 backup fails with 403 error"
```bash
# Verify AWS credentials
echo $AWS_ACCESS_KEY_ID
echo $AWS_SECRET_ACCESS_KEY

# Test S3 connectivity
php artisan tinker
Storage::disk('s3')->put('test.txt', 'test');
```

### "Backup file exceeds disk quota"
1. Check current disk usage: `df -h /path/to/storage`
2. Delete old backups: `php artisan backup:clean --force`
3. Move old backups to archive storage
4. Increase storage capacity if needed

## Backup Recovery

### Restore from Local Backup
```bash
# 1. Download backup from admin dashboard
# 2. Decrypt ZIP with password
# 3. Extract files
unzip -P your-password pageturner-backup-2026-04-19.zip

# 4. Restore database
mysql -u root -p pageturner < database.sql

# 5. Restore files
cp -r app/* /path/to/pageturner/app/
cp -r storage/app/book-covers/* /path/to/pageturner/storage/app/book-covers/
```

### Restore from S3 Backup
```bash
# 1. List backups in S3
aws s3 ls s3://pageturner-backups/

# 2. Download specific backup
aws s3 cp s3://pageturner-backups/pageturner-backup-2026-04-19.zip ./

# 3. Follow steps 2-5 from local restore
```

### Point-in-Time Recovery
- Backups are taken daily at 02:00 AM
- Recover to nearest backup date
- For more granular recovery, implement incremental backups (future enhancement)

## Best Practices

1. **Verify Backups Regularly**
   - Review health check status weekly
   - Download and test backup restore monthly
   - Verify S3 backups are syncing

2. **Monitor Task Execution**
   - Check maintenance_logs weekly
   - Review failed tasks immediately
   - Adjust task schedules if conflicts occur

3. **Secure Backup Password**
   - Store `BACKUP_ARCHIVE_PASSWORD` securely
   - Use strong, random password (minimum 32 characters)
   - Rotate password annually

4. **Archive Old Backups**
   - Move backups older than 1 year to archive storage
   - Consider off-site backup replication
   - Implement 3-2-1 backup strategy (3 copies, 2 media types, 1 off-site)

5. **Test Recovery Procedures**
   - Monthly: Download and extract backup
   - Quarterly: Full restore to staging environment
   - Annual: Document complete recovery procedure

## Command Reference

```bash
# Manually run backups
php artisan backup:run                          # Full backup
php artisan backup:clean                        # Cleanup old backups

# Maintenance tasks
php artisan order:cleanup-pending --hours=24    # Cancel old pending orders
php artisan session:cleanup                     # Clear expired sessions
php artisan log:rotate --days=30                # Archive logs older than 30 days
php artisan report:generate-daily --date=2026-04-19
php artisan notification:prune --days=90        # Delete old notifications
php artisan audit:archive --months=12           # Archive old audit logs

# Monitor backups
php artisan backup:monitor                      # Check backup health
php artisan backup:list                         # List all backups

# Scheduler debugging
php artisan schedule:work                       # Run scheduler in foreground
php artisan schedule:list                       # Show scheduled tasks
```

## Support & Maintenance

- **Laravel Backup Package**: https://spatie.be/docs/laravel-backup
- **PHP Documentation**: https://www.php.net/
- **MySQL Backup**: https://dev.mysql.com/doc/mysql-backup-excerpt/8.0/en/

## Version History

- **v1.0** (2026-04-19): Initial release
  - Automated daily backups at 02:00 AM
  - Retention policy: 7/14/4/12/2 days/weeks/months
  - Local encryption + S3 cloud backup
  - 8 scheduled maintenance tasks
  - Health check monitoring
  - Manual backup trigger
  - Full admin dashboard
