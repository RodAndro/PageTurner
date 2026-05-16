# Database Architecture Implementation Checklist

## Phase 1: Infrastructure Setup (Pre-Deployment)

### Master-Replica Replication Configuration

- [ ] **Set up primary database server (master)**
  - [ ] Install MySQL/MariaDB/PostgreSQL
  - [ ] Create application database
  - [ ] Create application user with privileges
  - [ ] Enable binary logging on master
  - [ ] Configure server ID (master = 1)
  - [ ] Create replication user
  
  ```bash
  # On Master
  mysql -u root -p -e "CREATE USER 'replication'@'%' IDENTIFIED BY 'rep_password';"
  mysql -u root -p -e "GRANT REPLICATION SLAVE ON *.* TO 'replication'@'%';"
  mysql -u root -p -e "SHOW MASTER STATUS;"
  ```

- [ ] **Set up read replica servers (1-2 replicas)**
  - [ ] Install MySQL/MariaDB/PostgreSQL (same version as master)
  - [ ] Configure server ID (replica = 2, 3, etc.)
  - [ ] Configure replication parameters
  - [ ] Start replication
  - [ ] Verify replication status
  
  ```bash
  # On Replicas
  mysql -u root -p -e "CHANGE MASTER TO MASTER_HOST='192.168.1.1', MASTER_USER='replication', MASTER_PASSWORD='rep_password';"
  mysql -u root -p -e "START SLAVE;"
  mysql -u root -p -e "SHOW SLAVE STATUS\G"
  ```

- [ ] **Test replication**
  - [ ] Create test table on master
  - [ ] Verify table appears on replicas within 5 seconds
  - [ ] Verify replication lag is < 1 second
  - [ ] Clean up test data

### Network and Security

- [ ] **Configure firewall rules**
  - [ ] Port 3306 (or configured port) open between master and replicas
  - [ ] Port 3306 open between application and master (writes)
  - [ ] Port 3306 open between application and replicas (reads)
  - [ ] SSL/TLS encryption for database connections

- [ ] **Set up monitoring**
  - [ ] Replication lag monitoring
  - [ ] Connection pool monitoring
  - [ ] Query performance monitoring

### Cache Infrastructure

- [ ] **Set up Redis server for query caching**
  - [ ] Install Redis
  - [ ] Configure persistence
  - [ ] Set memory eviction policy (recommended: allkeys-lru)
  - [ ] Enable authentication
  - [ ] Verify connectivity from application servers

  ```bash
  # Redis Configuration
  redis-cli CONFIG SET maxmemory 2gb
  redis-cli CONFIG SET maxmemory-policy allkeys-lru
  redis-cli CONFIG SET requirepass cache_password
  ```

---

## Phase 2: Code Implementation

### 2.1 Database Configuration

- [ ] **Update config/database.php**
  - [ ] Add read/write splitting for MySQL
  - [ ] Add read/write splitting for MariaDB
  - [ ] Add read/write splitting for PostgreSQL
  - [ ] Set sticky option to true

- [ ] **Create .env file**
  - [ ] Set DB_HOST (master)
  - [ ] Set DB_READ_HOST (comma-separated replicas)
  - [ ] Set DB_READ_WRITE_STICKY=true
  - [ ] Set CACHE_DRIVER=redis
  - [ ] Set REDIS_HOST and REDIS_PASSWORD

- [ ] **Test database connectivity**
  ```bash
  php artisan tinker
  > DB::connection('mysql')->table('users')->count()
  > DB::getPdo()
  ```

### 2.2 Database Indexes

- [ ] **Create and run indexing migration**
  ```bash
  php artisan migrate --path=database/migrations/2026_04_19_120000_add_database_indexes_for_performance.php
  ```

- [ ] **Verify indexes created**
  ```sql
  SHOW INDEX FROM books;
  SHOW INDEX FROM orders;
  SHOW INDEX FROM reviews;
  SHOW INDEX FROM categories;
  SHOW INDEX FROM audit_logs;
  ```

- [ ] **Monitor index usage**
  - [ ] Track index hit ratio
  - [ ] Identify unused indexes
  - [ ] Remove redundant indexes if needed

### 2.3 Query Caching Service

- [ ] **Create QueryCache service** (`app/Services/QueryCache.php`)
  - [ ] Implement cache methods for categories
  - [ ] Implement cache methods for books
  - [ ] Implement cache methods for orders
  - [ ] Implement cache methods for statistics
  - [ ] Implement cache invalidation methods

- [ ] **Test QueryCache in isolation**
  ```bash
  php artisan tinker
  > App\Services\QueryCache::getCategories()
  > App\Services\QueryCache::invalidateCategories()
  ```

### 2.4 Eager Loading Helper

- [ ] **Create EagerLoadHelper service** (`app/Services/EagerLoadHelper.php`)
  - [ ] Define booksList() pattern
  - [ ] Define bookDetail() pattern
  - [ ] Define ordersList() pattern
  - [ ] Define ordersForExport() pattern
  - [ ] Define userProfile() pattern

- [ ] **Test eager loading**
  ```bash
  php artisan tinker
  > App\Services\EagerLoadHelper::booksList()->get()
  ```

### 2.5 Model Observers

- [ ] **Create CategoryObserver** (`app/Observers/CategoryObserver.php`)
  - [ ] Implement created hook
  - [ ] Implement updated hook
  - [ ] Implement deleted hook

- [ ] **Create BookObserver** (`app/Observers/BookObserver.php`)
  - [ ] Implement cache invalidation for books
  - [ ] Handle cascade invalidation

- [ ] **Create OrderObserver** (`app/Observers/OrderObserver.php`)
  - [ ] Implement order cache invalidation

- [ ] **Register observers in AppServiceProvider**
  ```php
  // app/Providers/AppServiceProvider.php
  Category::observe(CategoryObserver::class);
  Book::observe(BookObserver::class);
  Order::observe(OrderObserver::class);
  ```

---

## Phase 3: Testing and Validation

### 3.1 Unit Tests

- [ ] **Test QueryCache methods**
  ```bash
  php artisan test tests/Unit/Services/QueryCacheTest.php
  ```

- [ ] **Test EagerLoadHelper**
  ```bash
  php artisan test tests/Unit/Services/EagerLoadHelperTest.php
  ```

- [ ] **Test Model Observers**
  ```bash
  php artisan test tests/Unit/Observers/
  ```

### 3.2 Performance Tests

- [ ] **Test N+1 query prevention**
  - [ ] Query count before eager loading: N+1 queries
  - [ ] Query count after eager loading: 2-3 queries
  - [ ] Verify improvement > 50%

- [ ] **Test query caching**
  - [ ] First call hits database
  - [ ] Subsequent calls hit cache
  - [ ] Cache hit ratio > 90%

- [ ] **Test read/write splitting**
  - [ ] Write operations: routed to master
  - [ ] Read operations: distributed across replicas
  - [ ] Verify no data loss on replicas

  ```bash
  # Monitor query routing
  mysql -h 192.168.1.1 -e "SHOW PROCESSLIST;" # Master queries
  mysql -h 192.168.1.2 -e "SHOW PROCESSLIST;" # Replica queries
  ```

### 3.3 Load Testing

- [ ] **Test under load**
  - [ ] 10 concurrent users
  - [ ] 50 concurrent users
  - [ ] 100 concurrent users

- [ ] **Measure metrics**
  - [ ] Response time degradation
  - [ ] Database connection count
  - [ ] Replication lag
  - [ ] Cache hit ratio

### 3.4 Integration Tests

- [ ] **Test write-then-read consistency**
  ```php
  // Create order
  $order = Order::create(['user_id' => 1, 'total' => 100]);
  
  // Read immediately (with sticky=true, should hit master)
  $fresh = Order::find($order->id);
  
  // Verify data matches
  $this->assertEquals($order->total, $fresh->total);
  ```

- [ ] **Test cache invalidation on updates**
  ```php
  $category = Category::first();
  $cached1 = QueryCache::getCategories();
  
  $category->update(['name' => 'New Name']);
  
  $cached2 = QueryCache::getCategories();
  
  // Verify updated name in cache
  $this->assertNotEquals($cached1[0]->name, 'New Name');
  $this->assertEquals($cached2[0]->name, 'New Name');
  ```

- [ ] **Test replication lag handling**
  - [ ] Simulate 5 second replication lag
  - [ ] Verify sticky sessions keep reads on master
  - [ ] Verify no stale data returned

---

## Phase 4: Deployment

### 4.1 Pre-Deployment

- [ ] **Review all changes**
  - [ ] Database configuration
  - [ ] Migration files
  - [ ] Service files
  - [ ] Observer registrations

- [ ] **Backup database**
  ```bash
  mysqldump -h 192.168.1.1 -u root -p pageturner_db > backup_$(date +%s).sql
  ```

- [ ] **Test on staging environment**
  - [ ] Run full test suite
  - [ ] Perform load testing
  - [ ] Verify monitoring alerts

### 4.2 Deployment Steps

1. **Deploy code**
   ```bash
   git pull origin main
   composer install --no-dev
   ```

2. **Run migrations** (includes indexes)
   ```bash
   php artisan migrate --force
   ```

3. **Verify indexes created**
   ```bash
   mysql pageturner_db -e "SELECT COUNT(*) as index_count FROM information_schema.statistics WHERE table_schema='pageturner_db';"
   ```

4. **Clear and warm cache**
   ```bash
   php artisan cache:clear
   php artisan cache:warm  # If available
   ```

5. **Monitor deployment**
   - [ ] Check replication lag
   - [ ] Monitor application errors
   - [ ] Verify cache hits
   - [ ] Check query performance

### 4.3 Post-Deployment

- [ ] **Verify all systems operational**
  - [ ] Master database accepting writes
  - [ ] Replicas synced with master
  - [ ] Cache layer responding
  - [ ] Application serving requests

- [ ] **Monitor for issues**
  - [ ] Replication lag spike detection
  - [ ] Slow query log analysis
  - [ ] Cache invalidation monitoring
  - [ ] Connection pool health

- [ ] **Performance baseline**
  - [ ] Record baseline metrics
  - [ ] Set alert thresholds
  - [ ] Document expected performance

---

## Phase 5: Monitoring and Maintenance

### 5.1 Daily Checks

- [ ] **Replication status**
  ```bash
  # Check all replicas
  for replica in 192.168.1.2 192.168.1.3; do
    echo "=== Replica: $replica ==="
    mysql -h $replica -u root -p -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master"
  done
  ```

- [ ] **Cache health**
  ```bash
  redis-cli info stats | grep "hits"
  ```

- [ ] **Query performance**
  - [ ] Review slow query log
  - [ ] Check query execution times

### 5.2 Weekly Reviews

- [ ] **Index effectiveness**
  - [ ] Are indexes being used?
  - [ ] Any missing indexes?
  - [ ] Remove unused indexes

- [ ] **Cache hit ratio**
  - [ ] Is cache hit ratio > 80%?
  - [ ] Adjust TTLs if needed

- [ ] **Replication lag trends**
  - [ ] Is lag increasing?
  - [ ] Any consistency issues?

### 5.3 Monthly Optimization

- [ ] **Query analysis**
  - [ ] Review slow query log
  - [ ] Optimize problematic queries
  - [ ] Consider adding new indexes

- [ ] **Capacity planning**
  - [ ] Analyze growth trends
  - [ ] Plan for scaling
  - [ ] Add replicas if needed

- [ ] **Cache strategy review**
  - [ ] Review cache hit/miss ratios
  - [ ] Adjust TTLs based on data volatility
  - [ ] Consider new cached queries

---

## Rollback Procedures

If issues occur, rollback steps:

### 5.1 Revert to Single Database

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    // Remove read/write splitting
    // ... other config
],
```

### 5.2 Disable Query Caching

```bash
php artisan cache:clear
# Update .env
CACHE_DRIVER=file
```

### 5.3 Disable Observers

```php
// app/Providers/AppServiceProvider.php
// Comment out observer registrations
// Category::observe(CategoryObserver::class);
// Book::observe(BookObserver::class);
// Order::observe(OrderObserver::class);
```

### 5.4 Rollback Indexes

```bash
php artisan migrate:rollback --path=database/migrations/2026_04_19_120000_add_database_indexes_for_performance.php
```

---

## Success Metrics

After implementation, you should see:

✅ **Performance**
- [ ] Read query latency reduced by 50-70%
- [ ] Write query latency < 10ms
- [ ] Cache hit ratio > 85%
- [ ] Average response time < 100ms

✅ **Reliability**
- [ ] Replication lag < 1 second (99% of time)
- [ ] Cache consistency > 99.9%
- [ ] Zero data loss incidents
- [ ] Automatic failover working

✅ **Scalability**
- [ ] Can handle 5-10x concurrent users
- [ ] Master CPU < 60%
- [ ] Replica CPU < 40%
- [ ] Query response times stable under load

✅ **Maintainability**
- [ ] Cache invalidation automatic
- [ ] N+1 queries eliminated
- [ ] Index usage optimized
- [ ] Replication stable

---

## Documentation and Handoff

- [ ] Create/update runbook for operations
- [ ] Document monitoring dashboard
- [ ] Train operations team on troubleshooting
- [ ] Schedule quarterly reviews
- [ ] Plan for future optimizations

