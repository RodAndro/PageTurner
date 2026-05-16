# Database Optimization - Observer Registration Guide

## Overview

Model Observers provide automatic cache invalidation when data changes. This ensures cache consistency without manual cache clearing in controllers.

## Registration

### Option 1: Register in AppServiceProvider (Recommended)

Edit `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Book;
use App\Models\Order;
use App\Observers\CategoryObserver;
use App\Observers\BookObserver;
use App\Observers\OrderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register model observers for cache invalidation
        Category::observe(CategoryObserver::class);
        Book::observe(BookObserver::class);
        Order::observe(OrderObserver::class);
    }
}
```

### Option 2: Register in Model (Alternative)

In each model, add to the `boot()` method:

```php
// app/Models/Category.php
protected static function boot()
{
    parent::boot();
    
    static::observe(CategoryObserver::class);
}
```

## Automatic Cache Invalidation Flow

### When Category is Updated

```
1. Category::update() called
2. Observer "updated" hook triggered
3. QueryCache::invalidateCategories() called
4. All category caches cleared
5. Next query fetches fresh data
```

### When Book is Created

```
1. Book::create() called
2. Observer "created" hook triggered
3. BookObserver invalidates:
   - Bestsellers cache
   - Recent books cache
   - Category books cache
   - Inventory statistics
4. Next queries fetch fresh data
```

### When Order Status Changes

```
1. Order::update(['status' => 'Shipped']) called
2. Observer "updated" hook triggered
3. OrderObserver invalidates:
   - Order statistics
   - Bestsellers (based on recent orders)
4. Next dashboard load shows updated stats
```

## Cache Invalidation Strategy

### Smart Invalidation

The observers implement smart invalidation that only clears affected caches:

```php
// Only clears relevant caches, not entire cache
QueryCache::invalidate('cache:key1', 'cache:key2', 'cache:key3');
```

### Cascade Invalidation

Some changes cascade through related caches:

```
Book Update
  ↓
Book caches invalidated
  ↓
Category book list invalidated
  ↓
Bestsellers/Recent invalidated
  ↓
Inventory stats invalidated
```

## Monitoring Cache Invalidations

### Enable Cache Logging

Add to `app/Providers/AppServiceProvider.php`:

```php
public function boot()
{
    // Log cache invalidations in development
    if ($this->app->isLocal()) {
        Event::listen(QueryExecuted::class, function ($query) {
            if (str_contains($query->sql, 'FORGET') || 
                str_contains($query->sql, 'FLUSH')) {
                \Log::debug('Cache cleared', ['query' => $query->sql]);
            }
        });
    }
}
```

### Check Cache Invalidation Count

```php
// Tinker: Check how many times cache was cleared
php artisan tinker
> \Cache::get('cache_invalidation_count') // Check custom counter
```

## Performance Impact

### Development Mode

Observers may add 1-2ms per write operation for cache invalidation.

### Production Mode

Negligible impact:
- Cache clearing is sub-millisecond in Redis
- Invalidated caches are lazy-loaded on first access
- No blocking operations

## Disabling Observers (Testing)

For unit tests, disable observers to test database independently:

```php
// tests/Feature/BookTest.php
public function setUp(): void
{
    parent::setUp();
    
    // Disable all observers for testing
    Model::withoutEvents(function () {
        // Your test code here
    });
}
```

Or per model:

```php
Book::withoutEvents(function () {
    Book::create([...]);
    // Cache not invalidated
});
```

## Example: Manual Cache Invalidation (When Observer Not Needed)

For one-off operations:

```php
// In a command or action
public function handle()
{
    // Bulk update books
    Book::whereCategory(1)->update(['price' => 99.99]);
    
    // Observer won't trigger for bulk updates, so invalidate manually
    QueryCache::invalidateBooks();
}
```

## Troubleshooting

### Cache Not Invalidating

1. **Check observers are registered**:
   ```php
   php artisan tinker
   > \App\Models\Book::$observers
   ```

2. **Verify cache is working**:
   ```php
   > Cache::put('test', 'value'); Cache::get('test')
   ```

3. **Check cache driver in production**:
   ```bash
   php artisan config:cache
   echo $CACHE_DRIVER
   ```

### Observer Not Triggering

Common issues:
- Using raw SQL queries (don't trigger observers)
- Bulk updates (Mass::update() skips observers)
- Missing model in observer registration

Solution: Use model methods instead of raw queries:

```php
// ❌ Raw query - won't trigger observer
DB::table('books')->update(['price' => 99.99]);

// ✅ Model query - triggers observer
Book::query()->update(['price' => 99.99]);

// ✅ Better - individual updates
foreach (Book::all() as $book) {
    $book->update(['price' => 99.99]); // Triggers observer
}
```

## Best Practices

1. **Let Observers Handle Cache Invalidation**
   - Don't manually call QueryCache::invalidate() in controllers
   - Trust the observer pattern to keep caches in sync

2. **Specific Invalidation**
   - Invalidate only affected caches, not entire cache
   - Use specific cache keys in observer invalidation

3. **Test Cache Invalidation**
   - Verify observers fire in tests
   - Check cache is cleared when expected

4. **Monitor in Production**
   - Track cache invalidation frequency
   - Alert if invalidations spike (data churn)

5. **Combine with Eager Loading**
   - Invalidate cache → observers handle it
   - Load with eager loading → prevents N+1
   - Use QueryCache → minimize database hits

## Summary

Model Observers provide:
✅ Automatic cache invalidation on data changes
✅ Consistent cache state with database
✅ Reduced manual cache management
✅ Cascade invalidation for related data
✅ Zero overhead in production with Redis

Integrate observers with QueryCache and EagerLoadHelper for complete database optimization.
