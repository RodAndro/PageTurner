# Testing and Validation Guide - Requirement 11

## Overview
Complete testing framework for PageTurner e-book platform covering import/export, backup/scheduling, audit/compliance, and rate limiting requirements.

**Test Files Created:**
- `tests/Feature/ImportExportTest.php` - 8 comprehensive tests
- `tests/Feature/BackupSchedulingTest.php` - 8 comprehensive tests
- `tests/Feature/AuditComplianceTest.php` - 11 comprehensive tests
- `tests/Feature/RateLimitingTest.php` - 12 comprehensive tests

**Total Test Cases:** 39+ comprehensive feature tests

---

## Running the Tests

### Run All Tests
```bash
php artisan test
# or
vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
# Import/Export tests only
php artisan test tests/Feature/ImportExportTest.php

# Backup/Scheduling tests only
php artisan test tests/Feature/BackupSchedulingTest.php

# Audit/Compliance tests only
php artisan test tests/Feature/AuditComplianceTest.php

# Rate Limiting tests only
php artisan test tests/Feature/RateLimitingTest.php
```

### Run with Coverage Report
```bash
php artisan test --coverage
php artisan test --coverage-html=coverage
```

### Run Specific Test
```bash
php artisan test tests/Feature/ImportExportTest.php --filter test_import_10000_book_records_successfully
```

---

## Requirement 11.1: Import/Export Testing

### Tests Implemented (8 tests)

#### ✅ 11.1.1: Import 10,000+ Records
```
Test: test_import_10000_book_records_successfully()
Description: Validates successful import of 10,000+ book records
Expected: 202 Accepted status, job queued for background processing
File: tests/Feature/ImportExportTest.php:19
```

**What It Tests:**
- Large file upload handling
- CSV parsing for 10,000 records
- Background job queuing
- Import log creation

**Command:**
```bash
php artisan test tests/Feature/ImportExportTest.php --filter test_import_10000_book_records_successfully
```

---

#### ✅ 11.1.2: Record Validation
```
Test: test_imported_records_validated_correctly()
Description: Validates that imported records are checked against schema
Expected: 100/100 records valid, 0 failures
File: tests/Feature/ImportExportTest.php:38
```

**What It Tests:**
- Field type validation
- Required field checking
- Data format validation
- Success rate calculation

---

#### ✅ 11.1.3: Malformed File Handling
```
Test: test_malformed_file_returns_proper_error_report()
Description: Ensures malformed CSV files produce detailed error messages
Expected: 400 Bad Request, detailed error list
File: tests/Feature/ImportExportTest.php:54
```

**What It Tests:**
- Invalid CSV format detection
- Column count validation
- Error message clarity
- User-friendly reporting

**Sample Error:**
```json
{
  "success": false,
  "errors": [
    "Invalid CSV format: Expected 6 columns, got 2",
    "Row 1: Missing required columns: isbn, category_id, price"
  ]
}
```

---

#### ✅ 11.1.4: Partial Import with Failures
```
Test: test_partial_import_with_failures_reported()
Description: Validates handling of partially successful imports
Expected: 950 successful, 50 failed, detailed error log
File: tests/Feature/ImportExportTest.php:72
```

**What It Tests:**
- Partial success handling
- Error collection
- Transaction rollback on error
- Failure reporting

---

#### ✅ 11.1.5: Queue Processing
```
Test: test_queue_processing_completes_background_job()
Description: Verifies queued jobs execute successfully
Expected: Job queued, executed, marked complete
File: tests/Feature/ImportExportTest.php:89
```

**What It Tests:**
- Job dispatching
- Queue execution
- Job status tracking
- Background processing flow

---

#### ✅ 11.1.6: Memory Efficiency (<256MB)
```
Test: test_import_memory_usage_under_256mb_with_chunking()
Description: Validates memory usage stays under 256MB during large import
Expected: Memory increase < 256MB, chunking enabled
File: tests/Feature/ImportExportTest.php:105
```

**What It Tests:**
- Memory profiling
- Chunking implementation
- Resource limits
- Garbage collection

**Memory Calculation:**
```
Initial Memory: 50 MB
Final Memory: 200 MB
Memory Increase: 150 MB ✓ (< 256MB limit)
```

---

#### ✅ 11.1.7: Export 50,000+ Records
```
Test: test_export_50000_records_without_timeout()
Description: Exports 50,000+ records without hitting timeout
Expected: 200 OK, CSV file generated, time < 300 seconds
File: tests/Feature/ImportExportTest.php:126
```

**What It Tests:**
- Large dataset export
- Timeout handling
- File generation
- Performance under load

---

#### ✅ 11.1.8: Streaming Export Memory Efficiency
```
Test: test_streaming_export_is_memory_efficient()
Description: Validates streaming export uses minimal memory for large datasets
Expected: Memory usage < 100MB for 50K records
File: tests/Feature/ImportExportTest.php:149
```

**What It Tests:**
- Streaming implementation
- Chunk-based processing
- Memory pooling
- Efficient iterators

---

## Requirement 11.2: Backup and Scheduling Testing

### Tests Implemented (8 tests)

#### ✅ 11.2.1: Manual Backup Execution
```
Test: test_manual_backup_execution_creates_backup_file()
Description: Creates backup on demand
Expected: 200 OK, backup_path in response
File: tests/Feature/BackupSchedulingTest.php:20
```

**Endpoint:**
```
POST /admin/backups/create
{
  "backup_type": "database",
  "name": "manual_backup_1713571200"
}
```

---

#### ✅ 11.2.2: Backup Verification
```
Test: test_backup_file_verified_with_checksum()
Description: Verifies backup integrity with SHA256 checksum
Expected: Checksum match = verified
File: tests/Feature/BackupSchedulingTest.php:39
```

**Verification Process:**
1. Create backup → checksum generated
2. Verify backup → recalculate checksum
3. Compare checksums
4. Return verification status

---

#### ✅ 11.2.3: Scheduled Task Automation
```
Test: test_scheduled_backup_task_runs_via_schedule()
Description: Scheduled tasks execute via php artisan schedule:run
Expected: Task executed, marked complete, timestamp updated
File: tests/Feature/BackupSchedulingTest.php:58
```

**Command:**
```bash
php artisan schedule:run
```

**Task Scheduling:**
```
0 2 * * * → Daily at 2 AM
0 */6 * * * → Every 6 hours
0 0 * * 0 → Weekly (Sunday)
```

---

#### ✅ 11.2.4: Backup Restoration
```
Test: test_backup_restoration_restores_data()
Description: Restores database from backup file
Expected: Data restored, matches pre-backup state
File: tests/Feature/BackupSchedulingTest.php:75
```

**Restoration Steps:**
1. Create test data
2. Create backup
3. Delete data
4. Restore from backup
5. Verify data recovered

---

#### ✅ 11.2.5: Failure Notifications
```
Test: test_backup_failure_triggers_notification()
Description: Admin notified when backup fails
Expected: Notification sent, error logged
File: tests/Feature/BackupSchedulingTest.php:102
```

**Notification Channels:**
- Email to admin
- Dashboard alert
- Audit log entry
- Slack webhook (if configured)

---

#### ✅ 11.2.6: Retention Policy Enforcement
```
Test: test_old_backups_deleted_according_to_retention_policy()
Description: Backups older than retention period automatically deleted
Expected: Old backups removed, recent backups kept
File: tests/Feature/BackupSchedulingTest.php:118
```

**Default Retention:**
- 30 days for daily backups
- 90 days for weekly backups
- 1 year for monthly backups

**Command:**
```bash
php artisan backups:enforce-retention
```

---

#### ✅ 11.2.7: Corruption Detection
```
Test: test_corrupted_backup_detected_during_integrity_check()
Description: Detects file corruption via checksum mismatch
Expected: Verification fails, corruption error returned
File: tests/Feature/BackupSchedulingTest.php:138
```

**Detection Method:**
```
Original Checksum: abc123def456...
After Corruption: xyz789uvw012...
Status: Checksum mismatch detected ❌
```

---

#### ✅ 11.2.8: Multiple Task Execution
```
Test: test_multiple_scheduled_tasks_execute_in_order()
Description: Multiple scheduled tasks execute in sequence
Expected: All tasks completed, execution order maintained
File: tests/Feature/BackupSchedulingTest.php:160
```

**Task Order:**
1. Pre-Backup Cleanup (1 AM)
2. Daily Backup (2 AM)
3. Post-Backup Verification (3 AM)

---

## Requirement 11.3: Audit and Compliance Testing

### Tests Implemented (11 tests)

#### ✅ 11.3.1: CREATE Creates Audit Entry
```
Test: test_create_operation_creates_audit_log_entry()
Description: All CREATE operations logged
Expected: Audit entry with event='created', new_values populated
File: tests/Feature/AuditComplianceTest.php:21
```

**Audit Entry:**
```json
{
  "event": "created",
  "auditable_type": "App\\Models\\Category",
  "auditable_id": 1,
  "new_values": { "name": "Test Category" },
  "user_id": 1,
  "created_at": "2026-04-19T15:30:00Z"
}
```

---

#### ✅ 11.3.2: UPDATE Captures Before/After
```
Test: test_update_operation_captures_old_and_new_values()
Description: UPDATE operations log old and new values
Expected: old_values and new_values both populated
File: tests/Feature/AuditComplianceTest.php:39
```

**Before/After Comparison:**
```json
{
  "event": "updated",
  "old_values": { "name": "Original Name" },
  "new_values": { "name": "Updated Name" },
  "diff": {
    "name": { "from": "Original Name", "to": "Updated Name" }
  }
}
```

---

#### ✅ 11.3.3: DELETE Creates Audit Entry
```
Test: test_delete_operation_creates_audit_log_entry()
Description: DELETE operations tracked
Expected: Audit entry with event='deleted', old_values preserved
File: tests/Feature/AuditComplianceTest.php:56
```

---

#### ✅ 11.3.4: Password Redaction
```
Test: test_passwords_redacted_from_audit_logs()
Description: Passwords excluded/redacted from audit logs
Expected: Password field absent or marked [REDACTED]
File: tests/Feature/AuditComplianceTest.php:72
```

**Redacted Fields:**
- password
- password_hash
- api_token
- two_factor_secret
- credit_card
- ssn

---

#### ✅ 11.3.5: PII Data Handling
```
Test: test_pii_data_redacted_or_excluded()
Description: Personally Identifiable Information protected
Expected: PII marked as sensitive or excluded
File: tests/Feature/AuditComplianceTest.php:93
```

**Protected PII Fields:**
- email (masked or minimized)
- phone (first 3 digits only)
- date_of_birth (year only or excluded)
- social_security_number (excluded)
- address (excluded or minimized)

---

#### ✅ 11.3.6: Search by Event Type
```
Test: test_audit_logs_searchable_by_event_type()
Description: Audit logs searchable by event (created, updated, deleted, etc.)
Expected: Query returns matching events
File: tests/Feature/AuditComplianceTest.php:111
```

**Search Query:**
```php
AuditLog::where('event', 'created')->get();
AuditLog::where('event', 'updated')->get();
AuditLog::where('event', 'deleted')->get();
```

---

#### ✅ 11.3.7: Date Range Filtering
```
Test: test_audit_logs_filterable_by_date_range()
Description: Filter audit logs by date range
Expected: Only logs within range returned
File: tests/Feature/AuditComplianceTest.php:128
```

**Filter Query:**
```php
AuditLog::where('created_at', '>=', now()->subDays(7))
         ->where('created_at', '<=', now())
         ->get();
```

---

#### ✅ 11.3.8: Filter by User
```
Test: test_audit_logs_filterable_by_user()
Description: Filter audit logs by user
Expected: Only logs for specific user returned
File: tests/Feature/AuditComplianceTest.php:147
```

**Filter Query:**
```php
AuditLog::where('user_id', $user->id)->get();
```

---

#### ✅ 11.3.9: Checksum Tamper Detection
```
Test: test_audit_log_checksum_detects_tampering()
Description: Detects if audit logs have been tampered with
Expected: Checksum mismatch indicates tampering
File: tests/Feature/AuditComplianceTest.php:166
```

**Tamper Detection:**
```
1. Original checksum calculated: abc123...
2. Data modified → new checksum: xyz789...
3. Mismatch detected ⚠️ TAMPER ALERT
```

---

#### ✅ 11.3.10: Complete Metadata Capture
```
Test: test_audit_log_includes_complete_metadata()
Description: Audit logs include IP, user agent, URL, method
Expected: Metadata object with all fields
File: tests/Feature/AuditComplianceTest.php:188
```

**Metadata Fields:**
```json
{
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "method": "POST",
  "url": "/api/books",
  "status_code": 201
}
```

---

#### ✅ 11.3.11: Bulk Operations
```
Test: test_bulk_operations_create_individual_audit_entries()
Description: Bulk operations create separate audit entry for each record
Expected: 10 records = 10 audit entries
File: tests/Feature/AuditComplianceTest.php:206
```

**Bulk Audit Behavior:**
```
Insert 10 categories
→ 10 separate audit_log entries created
→ Each with own event, timestamp, user_id
```

---

## Requirement 11.4: Rate Limiting Testing

### Tests Implemented (12 tests)

#### ✅ 11.4.1: Anonymous User Rate Limit
```
Test: test_anonymous_user_rate_limited()
Description: Anonymous users limited to configured requests/minute
Expected: 429 Too Many Requests after limit
File: tests/Feature/RateLimitingTest.php:23
```

**Configuration:**
```
Anonymous: 10 requests/minute
Customer: 100 requests/minute
Admin: Unlimited
```

---

#### ✅ 11.4.2: Customer Tier Higher Limit
```
Test: test_customer_user_has_higher_rate_limit()
Description: Customer users have higher limits than anonymous
Expected: Customer can make 100 requests vs anonymous 10
File: tests/Feature/RateLimitingTest.php:40
```

---

#### ✅ 11.4.3: Admin No Limit
```
Test: test_admin_user_not_rate_limited()
Description: Admin users bypass rate limiting
Expected: 200+ requests all succeed
File: tests/Feature/RateLimitingTest.php:57
```

---

#### ✅ 11.4.4: 429 Standard Headers
```
Test: test_rate_limit_429_includes_standard_headers()
Description: 429 responses include standard rate limit headers
Expected: Retry-After, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
File: tests/Feature/RateLimitingTest.php:73
```

**Header Example:**
```
HTTP/1.1 429 Too Many Requests
Retry-After: 45
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1713572400
```

---

#### ✅ 11.4.5: Remaining Requests Header
```
Test: test_rate_limit_headers_show_remaining_requests()
Description: Headers accurately show remaining requests
Expected: X-RateLimit-Remaining decrements correctly
File: tests/Feature/RateLimitingTest.php:93
```

**Header Progression:**
```
Request 1: X-RateLimit-Remaining: 9
Request 2: X-RateLimit-Remaining: 8
Request 3: X-RateLimit-Remaining: 7
...
Request 10: X-RateLimit-Remaining: 0 → 429 on next request
```

---

#### ✅ 11.4.6: Per-Second Burst Protection
```
Test: test_burst_requests_in_one_second_limited()
Description: Rapid requests in same second are limited
Expected: Some requests return 429 if burst > configured
File: tests/Feature/RateLimitingTest.php:108
```

**Burst Configuration:**
```
Per-second limit: 5 requests
Rapid requests (10): Some will be 429
```

---

#### ✅ 11.4.7: Rate Limit Reset
```
Test: test_rate_limit_resets_after_time_window()
Description: Rate limit resets after time window expires
Expected: Can make requests again after reset
File: tests/Feature/RateLimitingTest.php:131
```

**Reset Timeline:**
```
T0: Make 10 requests (limit reached)
T+1s to T+59s: All requests return 429
T+60s: Window reset → Can make requests again
```

---

#### ✅ 11.4.8: Graceful Degradation
```
Test: test_graceful_degradation_under_load()
Description: System handles load gracefully, some succeed, some limited
Expected: >50% success, <100% limited
File: tests/Feature/RateLimitingTest.php:154
```

**Load Test:**
- 5 users × 20 requests = 100 total
- Success: 50-80 requests
- Limited: 20-50 requests

---

#### ✅ 11.4.9: Separate Endpoint Limits
```
Test: test_separate_endpoints_have_separate_limits()
Description: Different endpoints have independent rate limits
Expected: /api/books limit ≠ /api/categories limit
File: tests/Feature/RateLimitingTest.php:177
```

**Endpoint Limits:**
```
GET /api/books → Limit A
GET /api/categories → Limit B (separate counter)
GET /api/authors → Limit C (separate counter)
```

---

#### ✅ 11.4.10: Premium User Custom Limit
```
Test: test_custom_rate_limit_for_premium_user()
Description: Premium tier users have higher custom limits
Expected: Premium can make 1000 requests vs customer 100
File: tests/Feature/RateLimitingTest.php:199
```

**Tier Progression:**
```
Free: 5 requests/min
Basic: 50 requests/min
Premium: 1000 requests/min
Enterprise: Unlimited
```

---

#### ✅ 11.4.11: Health Check Bypass
```
Test: test_health_check_endpoint_bypasses_rate_limit()
Description: Certain endpoints (health checks) bypass rate limiting
Expected: Health check succeeds even after limit exhausted
File: tests/Feature/RateLimitingTest.php:220
```

**Bypass Endpoints:**
- /health
- /status
- /ping
- /api/health

---

#### ✅ 11.4.12: Concurrent Requests
```
Test: test_concurrent_requests_share_same_rate_limit()
Description: Concurrent requests from same user share limit counter
Expected: Total requests across sessions = shared limit
File: tests/Feature/RateLimitingTest.php:238
```

**Concurrent Scenario:**
```
User A, Session 1: 10 requests
User A, Session 2: 10 requests
User A, Session 3: 10 requests
User A, Session 4: 10 requests
User A, Session 5: 10 requests

Total: 50 requests
Limit: 100
Remaining: 50
```

---

## Test Configuration

### PHPUnit Configuration
File: `phpunit.xml`

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
</php>
```

### Test Environment Variables
Create `.env.testing`:
```
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

---

## Running Tests with GitHub Actions

### CI/CD Pipeline Configuration
File: `.github/workflows/tests.yml`

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: pageturner_test
          MYSQL_ROOT_PASSWORD: root
    
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/setup-php@v1
        with:
          php-version: 8.2
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: php artisan test
      
      - name: Generate coverage
        run: php artisan test --coverage-html
```

---

## Test Results Expected

### When All Tests Pass
```
✓ ImportExportTest ........................... 8 tests passed
✓ BackupSchedulingTest ..................... 8 tests passed
✓ AuditComplianceTest ....................... 11 tests passed
✓ RateLimitingTest .......................... 12 tests passed

Total: 39 tests passed ✓ (XX seconds)
Coverage: YY%
```

### Performance Metrics
```
Total Test Duration: < 60 seconds
Average Test Duration: < 1.5 seconds
Slowest Test: < 5 seconds
```

---

## Troubleshooting Tests

### Issue: Tests fail with database errors
**Solution:** Run `php artisan migrate:fresh` before tests

### Issue: Queue tests fail
**Solution:** Set `QUEUE_CONNECTION=sync` in .env.testing

### Issue: Memory tests fail
**Solution:** Ensure sufficient system memory (>2GB recommended)

### Issue: Rate limit tests intermittent
**Solution:** Reset time with `\Carbon\Carbon::setTestNow(null)`

---

## Extending Tests

### Adding a New Test
```php
public function test_new_feature_works()
{
    // Arrange
    $data = // ... setup test data

    // Act
    $response = $this->post('/api/endpoint', $data);

    // Assert
    $response->assertStatus(200);
    $this->assertDatabaseHas('table', [...]);
}
```

### Test Database Factories
Use Laravel factories for test data:
```php
$user = User::factory()->create();
$books = Book::factory()->count(10)->create();
```

---

## Coverage Goals

| Component | Target | Current |
|-----------|--------|---------|
| Import/Export | 95% | TBD |
| Backup/Scheduling | 95% | TBD |
| Audit/Compliance | 95% | TBD |
| Rate Limiting | 90% | TBD |
| **Overall** | **93%** | **TBD** |

---

## Continuous Integration

### Pre-commit Hook
Run tests before committing:
```bash
#!/bin/bash
php artisan test
if [ $? -ne 0 ]; then
  echo "Tests failed!"
  exit 1
fi
```

### Pre-deployment
Run full test suite:
```bash
php artisan test --coverage --min=90
```

---

**Last Updated:** April 19, 2026
**Total Tests:** 39+
**Status:** Ready for Execution
