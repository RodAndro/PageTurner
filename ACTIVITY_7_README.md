# Activity 7

## Testing and Validation

This activity validates the PageTurner catalog performance requirements for large book datasets, optimized queries, caching, load handling, and export integrity.

## Hardware and Environment

Record the machine used for the screenshots:

- Device/CPU: ______________________________
- RAM: ______________________________
- Storage: ______________________________
- Operating System: Windows
- PHP Version: run `php -v`
- Laravel Version: run `php artisan --version`
- Database Driver: shown by `php artisan lab7:validate`
- Cache Driver: configured in `.env`
- Queue Driver: configured in `.env`

## Main Validation Command

Run this command to generate the full Activity 7 validation table:

```powershell
php -d memory_limit=512M artisan lab7:validate --repair-isbns --seed-records=1000000 --iterations=100 --search-iterations=50 --export-limit=50000
```

Screenshot the terminal output showing:

- 1,000,000 records seeded
- Memory usage below 512 MB
- Valid ISBN-13 results
- Query benchmark PASS results
- Cache validation PASS results
- Load validation PASS results
- Export validation PASS results

For a faster local test before the final screenshot:

```powershell
php artisan lab7:validate --repair-isbns --seed-records=1000 --iterations=10 --search-iterations=5 --export-limit=1000
```

## Query Benchmark Command

Run this command for the query-performance screenshot:

```powershell
php artisan benchmark:queries 100
```

Expected checks:

- ISBN lookup average is less than 50 ms
- Catalog listing average is less than 100 ms
- Category filter average is less than 150 ms
- Full-text search average is less than 300 ms
- Overall status shows all benchmark tests passed

## PHPUnit Validation

Run the related automated tests:

```powershell
php artisan test tests/Unit/BookFactoryTest.php
php artisan test tests/Unit/BookCacheServiceTest.php
php artisan test tests/Unit/ExportPerformanceTest.php
```

Optional full test suite:

```powershell
php artisan test
```

## Implemented Deliverables

| File | Purpose |
| --- | --- |
| `database/factories/BookFactory.php` | Generates realistic book data and valid ISBN-13 values |
| `database/seeders/MassBookSeeder.php` | Chunked batch insert seeder for large datasets |
| `app/Repositories/BookRepository.php` | Optimized data access layer |
| `app/Services/BookCacheService.php` | Cache abstraction with category tag invalidation support |
| `app/Observers/BookObserver.php` | Invalidates book, catalog, and category caches on model changes |
| `app/Console/Commands/BenchmarkBookQueries.php` | Query performance benchmark command |
| `app/Console/Commands/LabActivity7Validation.php` | Screenshot-friendly Activity 7 validation command |
| `app/Jobs/WarmCategoryCache.php` | Background cache warming job |
| `app/Jobs/LargeBookExport.php` | Chunked export job for large book exports |
| `config/scout.php` | Laravel Scout search configuration |
| `database/migrations/*optimize*` | Index, partition, and performance-related migrations |
| `.env.example` | Redis, cache, queue, Scout, and database environment templates |
| `config/database.php` | Database connection configuration |
| `config/cache.php` | Cache store configuration |

## Checklist Evidence

### 7.1 Seeding Performance Tests

- [ ] 1M records seeded in less than 10 minutes
- [ ] Memory usage stays below 512 MB
- [ ] All ISBNs are valid with checksum verification
- [ ] Foreign keys reference valid category records
- [ ] Factory generates realistic data distributions

### 7.2 Query Performance Tests

- [ ] ISBN lookup is less than 50 ms average
- [ ] Catalog listing is less than 100 ms average
- [ ] Category filter is less than 150 ms average
- [ ] Full-text search is less than 300 ms average
- [ ] No N+1 query problems detected

### 7.3 Cache Validation

- [ ] Repeated catalog requests serve from cache in less than 10 ms
- [ ] Cache invalidation works correctly on book update
- [ ] Redis memory usage is monitored and bounded
- [ ] Cache tags function correctly for category-specific invalidation

### 7.4 Load Testing

- [ ] System handles 50 catalog requests without error
- [ ] Rate limiting configuration is present
- [ ] Read replica traffic is verified through query logs when read replicas are enabled
- [ ] Queue workers process indexing/export jobs without backlog

### 7.5 Data Integrity

- [ ] 1M records are queryable via Eloquent without timeout
- [ ] Export of 50K records completes without memory exhaustion
- [ ] Partition pruning is verified with `EXPLAIN` when database partitioning is enabled

## Notes

The local validation command reports read replicas, queue backlog, and partition pruning as informational checks if the current environment does not expose those production features. The terminal output should still be included as evidence, and production-only checks should be supported with query logs or screenshots when available.
