# Activity 6

## Deliverables

This document summarizes the Activity 6 submission requirements for import/export operations, scheduled maintenance tasks, rate limiting, response transformation, backup configuration, and audit logging.

## 1. Complete Source Code Repository

### Import and Export Classes

The project includes Laravel Excel import/export classes and supporting queue jobs for large file processing.

| File | Purpose |
| --- | --- |
| `app/Imports/BooksImport.php` | Imports book records from spreadsheet/CSV files |
| `app/Imports/UsersImport.php` | Imports user records from spreadsheet/CSV files |
| `app/Exports/BooksExport.php` | Exports book records |
| `app/Exports/UsersExport.php` | Exports user records |
| `app/Exports/OrdersExport.php` | Exports order records |
| `app/Exports/BooksFromQueryExport.php` | Exports filtered book query results |
| `app/Concerns/HandlesLargeImports.php` | Shared helper logic for chunked large imports |
| `app/Jobs/ProcessBookImport.php` | Queued processing for book imports |
| `app/Jobs/ProcessUserImport.php` | Queued processing for user imports |
| `app/Jobs/ProcessImportJob.php` | General queued import processing |
| `app/Jobs/ProcessExport.php` | General queued export processing |

### Custom Artisan Commands for Scheduled Tasks

| File | Command Purpose |
| --- | --- |
| `app/Console/Commands/RunScheduledTasks.php` | Runs configured scheduled maintenance tasks |
| `app/Console/Commands/CleanupBackupsCommand.php` | Removes old backup files based on retention rules |
| `app/Console/Commands/CleanupSessionsCommand.php` | Cleans expired sessions |
| `app/Console/Commands/CleanupPendingOrdersCommand.php` | Cleans pending or abandoned orders |
| `app/Console/Commands/PruneNotificationsCommand.php` | Removes old notifications |
| `app/Console/Commands/RotateLogsCommand.php` | Rotates application logs |
| `app/Console/Commands/GenerateDailyReportCommand.php` | Generates daily reports |
| `app/Console/Commands/ArchiveAuditLogsCommand.php` | Archives old audit log records |
| `app/Console/Kernel.php` | Registers scheduled commands and schedule frequency |
| `routes/console.php` | Defines additional console schedule entries |

### Rate Limiter Configurations

| File | Purpose |
| --- | --- |
| `config/rate-limits.php` | Tiered rate-limit configuration |
| `app/Services/RateLimiterService.php` | Central rate-limit service logic |
| `app/Http/Middleware/RateLimitMiddleware.php` | Basic request throttling middleware |
| `app/Http/Middleware/RateLimiterMiddleware.php` | Rate limiter middleware integration |
| `app/Http/Middleware/TieredRateLimitMiddleware.php` | Tier-aware throttling middleware |
| `database/seeders/ApiRateLimitSeeder.php` | Seeds API rate-limit records |
| `app/Models/ApiRateLimit.php` | API rate-limit model |

### Middleware for Data Transformation

| File | Purpose |
| --- | --- |
| `app/Http/Middleware/TransformResponse.php` | Transforms API responses into a consistent format |
| `app/Http/Middleware/FilterFields.php` | Supports selecting/filtering fields in responses |
| `app/Http/Middleware/ETagMiddleware.php` | Adds ETag support for response caching |

## 2. Database Migrations and Seeders

### Migrations

| File | Purpose |
| --- | --- |
| `database/migrations/2026_04_19_000000_create_import_export_logs_table.php` | Creates import/export tracking logs |
| `database/migrations/2026_04_19_100000_create_backup_logs_table.php` | Creates backup log records |
| `database/migrations/2026_04_19_110000_create_audit_logs_table.php` | Creates audit logging table |
| `database/migrations/2026_04_19_130002_create_scheduled_tasks_table.php` | Creates scheduled task records |
| `database/migrations/2026_04_19_130003_create_api_rate_limits_table.php` | Creates API rate-limit records |
| `database/migrations/2026_04_19_130004_create_backup_monitoring_table.php` | Creates backup monitoring records |
| `database/migrations/2026_05_16_000000_add_audit_compatibility_columns.php` | Adds audit compatibility fields |

### Seeders

| File | Purpose |
| --- | --- |
| `database/seeders/ImportLogSeeder.php` | Seeds sample import logs |
| `database/seeders/ExportLogSeeder.php` | Seeds sample export logs |
| `database/seeders/ScheduledTaskSeeder.php` | Seeds scheduled maintenance task records |
| `database/seeders/ApiRateLimitSeeder.php` | Seeds API rate-limit tiers/rules |
| `database/seeders/BackupMonitoringSeeder.php` | Seeds backup monitoring data |
| `database/seeders/DatabaseSeeder.php` | Registers project seeders |

## 3. Configuration Files

| File | Description |
| --- | --- |
| `config/backup.php` | Backup scheduling, storage disks, retention, cleanup, and notification settings |
| `config/excel.php` | Laravel Excel import/export settings, chunk size, CSV options, cache, and transactions |
| `config/audit.php` | Audit logging configuration for tracked models, events, storage, and retention |
| `config/queue.php` | Queue connection settings for import/export jobs |
| `config/cache.php` | Cache configuration used by performance and API response features |
| `.env.example` | Environment variables for database, queue, cache, backup, and service configuration |

## 4. Documentation

The documentation deliverables are included in the repository.

| File | Description |
| --- | --- |
| `docs/TECHNICAL_DOCUMENTATION.md` | Technical report covering architecture, chunking, queuing, backup strategy, audit security, and performance optimization |
| `docs/API_DOCUMENTATION.md` | API documentation for endpoints, response format, and rate limits |
| `docs/USER_GUIDE.md` | User guide for import/export workflows and admin operations |
| `IMPORT_EXPORT_DOCUMENTATION.md` | Additional import/export feature documentation |
| `IMPORT_EXPORT_DASHBOARD_IMPLEMENTATION.md` | Admin dashboard implementation details |
| `API_RATE_LIMITING.md` | Rate limiting guide |
| `API_RATE_LIMITING_IMPLEMENTATION.md` | Rate limiting implementation notes |
| `API_RATE_LIMITING_SECURITY.md` | Security considerations for API rate limiting |
| `BACKUP_MAINTENANCE_DOCUMENTATION.md` | Backup and maintenance documentation |
| `AUDIT_LOG_IMPLEMENTATION.md` | Audit logging implementation details |
| `AUDIT_LOG_SETUP.md` | Audit setup instructions |

## Technical Report Coverage

The technical documentation should cover the following topics:

- Architecture decisions for chunked imports and queued export jobs
- Why large imports/exports are processed with jobs instead of a single request
- Backup schedule, storage location, retention period, and restore procedure
- Disaster recovery process after database or file loss
- Audit logging security, tamper detection, and sensitive data protection
- Rate limiting as protection against excessive API usage
- Performance optimization through chunking, queues, indexes, caching, and streaming responses

## Commands for Screenshots

Run these commands and capture terminal output for submission evidence.

```powershell
php artisan migrate
php artisan db:seed --class=ApiRateLimitSeeder
php artisan db:seed --class=ScheduledTaskSeeder
php artisan db:seed --class=BackupMonitoringSeeder
```

Show scheduled commands:

```powershell
php artisan schedule:list
```

Run related tests:

```powershell
php artisan test tests/Feature/ImportExportTest.php
php artisan test tests/Feature/RateLimitingTest.php
php artisan test tests/Feature/BackupSchedulingTest.php
php artisan test tests/Feature/AuditComplianceTest.php
```

Run custom workflow checks if needed:

```powershell
php test_full_export_workflow.php
php test_export.php
php test_orders_export.php
```

## Submission Checklist

- [ ] Source code repository includes import/export classes using Laravel Excel concerns
- [ ] Source code repository includes queued import/export jobs
- [ ] Custom Artisan commands for scheduled tasks are implemented
- [ ] Rate limiter configuration and middleware are included
- [ ] Data transformation middleware is included
- [ ] New database migrations are included
- [ ] New database seeders are included
- [ ] `config/backup.php` is configured
- [ ] `config/excel.php` is configured
- [ ] `config/audit.php` is configured
- [ ] Technical report is included and is at least 1500 words
- [ ] API documentation includes rate limits and data endpoints
- [ ] User guide explains import/export operations
- [ ] Screenshots or terminal logs are captured for migrations, seeders, scheduled commands, and tests

## Notes

Before final submission, verify that `.env` values match the environment used during screenshots. Queue-backed features should be tested with a running queue worker when demonstrating real asynchronous import/export behavior:

```powershell
php artisan queue:work
```
