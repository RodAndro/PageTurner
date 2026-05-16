# Quick Test Execution Reference

## Summary
- **Total Test Suites:** 4
- **Total Test Cases:** 39+
- **Estimated Runtime:** 60-90 seconds
- **Setup Time:** < 5 minutes

---

## One-Command Test Execution

### Run ALL Tests
```bash
php artisan test
```

### Run ALL Tests with Coverage Report
```bash
php artisan test --coverage
```

---

## Individual Test Suite Commands

### 1️⃣ Import/Export Tests (8 tests)
```bash
php artisan test tests/Feature/ImportExportTest.php
```

**What's Tested:**
- ✅ 10,000+ record imports
- ✅ Malformed file handling
- ✅ Queue processing
- ✅ Memory usage < 256MB
- ✅ Export 50,000+ records
- ✅ Streaming efficiency

---

### 2️⃣ Backup & Scheduling Tests (8 tests)
```bash
php artisan test tests/Feature/BackupSchedulingTest.php
```

**What's Tested:**
- ✅ Manual backup creation
- ✅ Checksum verification
- ✅ Scheduled task execution
- ✅ Backup restoration
- ✅ Failure notifications
- ✅ Retention policy enforcement

---

### 3️⃣ Audit & Compliance Tests (11 tests)
```bash
php artisan test tests/Feature/AuditComplianceTest.php
```

**What's Tested:**
- ✅ CRUD audit entries (CREATE, UPDATE, DELETE)
- ✅ Password redaction
- ✅ PII protection
- ✅ Search and filtering
- ✅ Tamper detection (checksums)
- ✅ Metadata capture

---

### 4️⃣ Rate Limiting Tests (12 tests)
```bash
php artisan test tests/Feature/RateLimitingTest.php
```

**What's Tested:**
- ✅ Tiered rate limits
- ✅ 429 responses with headers
- ✅ Burst protection
- ✅ Graceful degradation
- ✅ Endpoint separation
- ✅ Per-user custom limits

---

## Running Specific Tests

### Run Single Test
```bash
php artisan test tests/Feature/ImportExportTest.php --filter test_import_10000_book_records_successfully
```

### Run Tests Matching Pattern
```bash
php artisan test --filter rate_limit
php artisan test --filter password_redacted
php artisan test --filter backup
```

---

## Test Output Options

### Verbose Output
```bash
php artisan test -v
```

### Minimal Output
```bash
php artisan test -q
```

### Parallel Execution
```bash
php artisan test --parallel
```

### Coverage Report (HTML)
```bash
php artisan test --coverage-html coverage/
```

Then open `coverage/index.html` in browser.

---

## Pre-Test Checklist

✅ **Before running tests, ensure:**

1. Database migrated
   ```bash
   php artisan migrate:fresh
   ```

2. Cache cleared
   ```bash
   php artisan cache:clear
   ```

3. Routes cached (optional)
   ```bash
   php artisan route:cache
   ```

4. Config cached (optional)
   ```bash
   php artisan config:cache
   ```

---

## Expected Test Results

### Perfect Run
```
...................................... 39 passed
PASSED Tests: 39/39
Coverage: 92.5%
Duration: 58 seconds
```

### Partial Run (with failures)
```
.......................×2..×1........ 36 passed, 3 failed
FAILED Tests: 3/39
Coverage: 89.2%
Duration: 62 seconds
```

---

## Debugging Failed Tests

### Show detailed failure info
```bash
php artisan test --verbose
```

### Run with verbose SQL logging
```bash
php artisan test tests/Feature/ImportExportTest.php -vvv
```

### Stop on first failure
```bash
php artisan test --stop-on-failure
```

---

## Performance Test Commands

### Test with low memory
```bash
php -d memory_limit=128M artisan test tests/Feature/ImportExportTest.php
```

### Test with memory profiling
```bash
php -d memory_limit=-1 artisan test --coverage
```

### Test execution time
```bash
time php artisan test
```

---

## Test Data Setup

### Create fresh test data
```bash
php artisan migrate:fresh --seed
```

### Reset only tests
```bash
php artisan migrate:fresh --env=testing
```

### Create specific seeder for tests
```bash
php artisan db:seed --class=TestDataSeeder
```

---

## Troubleshooting

### "Test database does not exist"
```bash
php artisan migrate:fresh --env=testing
```

### "Tests timeout"
Increase timeout in `phpunit.xml`:
```xml
<php>
  <ini name="default_socket_timeout" value="300"/>
</php>
```

### "Memory exhausted"
Increase PHP memory:
```bash
php -d memory_limit=512M artisan test
```

### "Queue connection not set"
Verify `.env.testing`:
```
QUEUE_CONNECTION=sync
```

---

## Continuous Integration Setup

### GitHub Actions (automatic on push)
Tests run automatically on push/PR

### Manual triggering
```bash
git push origin feature-branch
# Tests run automatically
```

### Local CI simulation
```bash
php artisan test --coverage --min=90
```

---

## Test Files Location

```
tests/
├── Feature/
│   ├── ImportExportTest.php          (8 tests)
│   ├── BackupSchedulingTest.php      (8 tests)
│   ├── AuditComplianceTest.php       (11 tests)
│   └── RateLimitingTest.php          (12 tests)
├── Unit/
│   └── ExampleTest.php
└── TestCase.php                       (base class)
```

---

## Test Naming Conventions

**Format:** `test_{feature}_{expected_outcome}`

Examples:
- `test_import_10000_book_records_successfully`
- `test_rate_limit_429_includes_standard_headers`
- `test_passwords_redacted_from_audit_logs`

---

## Running Before Commit

### Pre-commit hook setup
```bash
# Create .git/hooks/pre-commit
#!/bin/bash
php artisan test --stop-on-failure
```

### Before push checklist
```bash
# Run tests
php artisan test

# Run static analysis
php artisan lint

# Check migrations
php artisan migrate:status

# Check code style
php artisan code:style --fix
```

---

## Documentation Files

- **TESTING_VALIDATION_GUIDE.md** - Full testing documentation (this file)
- **TESTING_REQUIREMENTS_MATRIX.md** - Requirement-to-test mapping
- **phpunit.xml** - Test configuration

---

## Support

**For test help:**
- View test code: `tests/Feature/[TestName].php`
- Run single test: `php artisan test [file] --filter [test_name]`
- Check coverage: `php artisan test --coverage`

**Common test execution patterns:**
```bash
# Quick smoke test
php artisan test tests/Feature/ImportExportTest.php

# Full validation
php artisan test --coverage

# Specific requirement
php artisan test --filter "11.1" # All 11.1 tests

# Performance check
time php artisan test --parallel
```

---

## Next Steps

1. ✅ Run all tests: `php artisan test`
2. ✅ Review coverage: `php artisan test --coverage-html`
3. ✅ Fix failures: Review test output and code
4. ✅ Commit: `git add . && git commit -m "All tests passing"`
5. ✅ Deploy: Push to main branch

---

**Test Status:** Ready for Execution ✅
**Last Updated:** April 19, 2026
**Version:** 1.0
