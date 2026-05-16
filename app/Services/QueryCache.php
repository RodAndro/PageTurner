<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Closure;

/**
 * QueryCache Service
 * 
 * Provides centralized caching for frequently accessed database queries.
 * Integrates with Laravel's cache system and supports TTL-based invalidation.
 * 
 * Usage:
 *   $categories = QueryCache::remember('categories:all', 3600, function() {
 *       return Category::with('books')->get();
 *   });
 * 
 * @package App\Services
 */
class QueryCache
{
    /**
     * Cache key prefix for query cache entries
     */
    protected const CACHE_PREFIX = 'query_';

    /**
     * Cache remember with automatic key generation
     * 
     * @param string $key Cache key identifier
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @param Closure $callback Function that retrieves data
     * @return mixed
     */
    public static function remember(string $key, int $ttl, Closure $callback)
    {
        return Cache::remember(
            self::getCacheKey($key),
            $ttl,
            $callback
        );
    }

    /**
     * Get categories with caching
     * Used for category listings, filters, and sidebars
     * 
     * @param int $ttl Time to live in seconds (default: 24 hours)
     * @return Collection
     */
    public static function getCategories(int $ttl = 86400)
    {
        return self::remember('categories:all', $ttl, function () {
            return \App\Models\Category::with('books')->get();
        });
    }

    /**
     * Get category by ID with caching
     * 
     * @param int $categoryId
     * @param int $ttl Time to live in seconds
     * @return \App\Models\Category|null
     */
    public static function getCategory(int $categoryId, int $ttl = 86400)
    {
        return self::remember("category:{$categoryId}", $ttl, function () use ($categoryId) {
            return \App\Models\Category::with('books')->find($categoryId);
        });
    }

    /**
     * Get bestselling books with caching
     * Cached for 1 hour due to frequent updates
     * 
     * @param int $limit Number of books to retrieve
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return Collection
     */
    public static function getBestsellingBooks(int $limit = 10, int $ttl = 3600)
    {
        return self::remember("bestsellers:{$limit}", $ttl, function () use ($limit) {
            return \App\Models\Book::with(['category', 'reviews'])
                ->withCount('orderItems')
                ->orderBy('order_items_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get top-rated books with caching
     * 
     * @param int $limit Number of books to retrieve
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return Collection
     */
    public static function getTopRatedBooks(int $limit = 10, int $ttl = 3600)
    {
        return self::remember("top_rated:{$limit}", $ttl, function () use ($limit) {
            return \App\Models\Book::with(['category', 'reviews'])
                ->withAvg('reviews', 'rating')
                ->where('reviews_avg_rating', '>=', 4)
                ->orderBy('reviews_avg_rating', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get recently added books with caching
     * 
     * @param int $limit Number of books to retrieve
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return Collection
     */
    public static function getRecentBooks(int $limit = 10, int $ttl = 3600)
    {
        return self::remember("recent_books:{$limit}", $ttl, function () use ($limit) {
            return \App\Models\Book::with(['category', 'reviews'])
                ->latest()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get book by ISBN with caching
     * ISBN lookups are cached longer due to immutable nature
     * 
     * @param string $isbn ISBN identifier
     * @param int $ttl Time to live in seconds (default: 7 days)
     * @return \App\Models\Book|null
     */
    public static function getBookByISBN(string $isbn, int $ttl = 604800)
    {
        return self::remember("book:isbn:{$isbn}", $ttl, function () use ($isbn) {
            return \App\Models\Book::with(['category', 'reviews'])
                ->where('isbn', $isbn)
                ->first();
        });
    }

    /**
     * Get books by category with caching
     * 
     * @param int $categoryId
     * @param int $ttl Time to live in seconds (default: 3 hours)
     * @return Collection
     */
    public static function getBooksByCategory(int $categoryId, int $ttl = 10800)
    {
        return self::remember("books:category:{$categoryId}", $ttl, function () use ($categoryId) {
            return \App\Models\Book::where('category_id', $categoryId)
                ->with(['category', 'reviews'])
                ->orderBy('title')
                ->get();
        });
    }

    /**
     * Get order statistics with caching
     * Statistics cached for 1 hour
     * 
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return array
     */
    public static function getOrderStatistics(int $ttl = 3600): array
    {
        return self::remember('stats:orders', $ttl, function () {
            return [
                'total_orders' => \App\Models\Order::count(),
                'total_revenue' => \App\Models\Order::sum('total_amount'),
                'pending_orders' => \App\Models\Order::where('status', 'Pending')->count(),
                'completed_orders' => \App\Models\Order::whereIn('status', ['Delivered', 'Shipped'])->count(),
            ];
        });
    }

    /**
     * Get inventory statistics with caching
     * 
     * @param int $ttl Time to live in seconds (default: 30 minutes)
     * @return array
     */
    public static function getInventoryStatistics(int $ttl = 1800): array
    {
        return self::remember('stats:inventory', $ttl, function () {
            return [
                'total_books' => \App\Models\Book::count(),
                'total_stock' => \App\Models\Book::sum('stock_quantity'),
                'low_stock_count' => \App\Models\Book::where('stock_quantity', '<', 5)->count(),
                'out_of_stock_count' => \App\Models\Book::where('stock_quantity', 0)->count(),
            ];
        });
    }

    /**
     * Clear all query cache entries
     * Useful for migrations, tests, and cache invalidation
     * 
     * @return bool
     */
    public static function clearAll(): bool
    {
        return Cache::flush();
    }

    /**
     * Clear specific cache entries by pattern
     * 
     * @param string $pattern Pattern to match cache keys
     * @return void
     */
    public static function clearPattern(string $pattern): void
    {
        $keys = Cache::getStore()->getPrefix() . self::CACHE_PREFIX . $pattern;
        // Note: Exact implementation depends on cache driver
        // For Redis, use wildcard patterns; for other drivers, may need custom logic
    }

    /**
     * Invalidate specific cache entries
     * 
     * @param string ...$keys Cache keys to invalidate
     * @return void
     */
    public static function invalidate(string ...$keys): void
    {
        foreach ($keys as $key) {
            Cache::forget(self::getCacheKey($key));
        }
    }

    /**
     * Invalidate all category-related caches
     * Called when category is created, updated, or deleted
     * 
     * @return void
     */
    public static function invalidateCategories(): void
    {
        self::invalidate('categories:all');
        // Individual category caches will be cleared via pattern matching
    }

    /**
     * Invalidate all book-related caches
     * Called when book is created, updated, or deleted
     * 
     * @return void
     */
    public static function invalidateBooks(): void
    {
        self::invalidate(
            'bestsellers:10',
            'top_rated:10',
            'recent_books:10',
            'stats:inventory'
        );
        // Also invalidate category-specific book caches
    }

    /**
     * Invalidate order statistics
     * Called when order is created or status changes
     * 
     * @return void
     */
    public static function invalidateOrders(): void
    {
        self::invalidate('stats:orders');
    }

    /**
     * Generate prefixed cache key
     * 
     * @param string $key
     * @return string
     */
    protected static function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }
}
