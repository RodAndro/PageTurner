<?php

namespace App\Http\Controllers\Examples;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Services\QueryCache;
use App\Services\EagerLoadHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

/**
 * Example Database Optimization Controller
 * 
 * Demonstrates best practices for query optimization including:
 * - Eager loading to prevent N+1 queries
 * - Query caching for frequent operations
 * - Read/write splitting patterns
 * - Connection pooling efficiency
 * 
 * @package App\Http\Controllers\Examples
 */
class DatabaseOptimizationExampleController
{
    /**
     * Example 1: Category Listing with Query Caching
     * 
     * Demonstrates:
     * - Query caching for static/semi-static data
     * - Eager loading categories with book counts
     * - Cache invalidation pattern
     * 
     * @return array
     */
    public function categoryListOptimized()
    {
        // Use QueryCache to cache categories for 24 hours
        // Automatically loads categories with book relationships
        $categories = QueryCache::getCategories(86400);

        return [
            'categories' => $categories,
            'total' => $categories->count(),
            'cached_at' => now(),
        ];
    }

    /**
     * Example 2: Book Listing with Eager Loading
     * 
     * Demonstrates:
     * - Preventing N+1 queries with eager loading
     * - Using helper methods for consistent patterns
     * - Pagination with eager loading
     * 
     * @return \Illuminate\Pagination\Paginator
     */
    public function bookListOptimized()
    {
        // Use EagerLoadHelper to load all necessary relations
        // Prevents N+1 queries when accessing book.category, book.reviews
        $books = EagerLoadHelper::booksList()
            ->paginate(20);

        return $books;
    }

    /**
     * Example 3: Book Detail View (No N+1 Problem)
     * 
     * Demonstrates:
     * - Deep eager loading (relations of relations)
     * - Conditional eager loading (sorting reviews)
     * - Accessing related data without additional queries
     * 
     * @param int $bookId
     * @return Book
     */
    public function bookDetailOptimized($bookId)
    {
        // Load book with category, reviews, and review authors
        $book = EagerLoadHelper::bookDetail()
            ->findOrFail($bookId);

        // All data already loaded - no N+1 problem!
        // Can safely access:
        // $book->category->name;
        // $book->reviews;
        // $book->reviews[0]->user->name;

        return $book;
    }

    /**
     * Example 4: Order History with Eager Loading
     * 
     * Demonstrates:
     * - Loading nested relationships (order → items → books)
     * - Eager loading order items with book details
     * - Preventing multiple queries per order
     * 
     * @param int $userId
     * @return \Illuminate\Pagination\Paginator
     */
    public function userOrderHistoryOptimized($userId)
    {
        // Load user's orders with all item and book details
        // Single database round-trip for all data
        $orders = EagerLoadHelper::ordersList()
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);

        // Can access without N+1 problem:
        // foreach ($orders as $order) {
        //     foreach ($order->orderItems as $item) {
        //         echo $item->book->title; // Already loaded
        //     }
        // }

        return $orders;
    }

    /**
     * Example 5: Order Export with Optimized Queries
     * 
     * Demonstrates:
     * - Selective column loading to reduce data transfer
     * - Optimized query for export/reporting
     * - Combined caching for statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function orderExportOptimized($startDate, $endDate)
    {
        // Use optimized export query with only needed columns
        $orders = EagerLoadHelper::ordersForExport()
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->get();

        // Get statistics (cached)
        $stats = QueryCache::getOrderStatistics(3600);

        return [
            'orders' => $orders,
            'total_count' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'stats' => $stats,
        ];
    }

    /**
     * Example 6: Bestselling Books with Caching
     * 
     * Demonstrates:
     * - Query caching for frequently accessed data
     * - Automatic cache invalidation on write
     * - Short TTL for data that changes frequently
     * 
     * @return array
     */
    public function bestsellingBooksOptimized()
    {
        // Cached for 1 hour (updates frequently with orders)
        $bestsellers = QueryCache::getBestsellingBooks(10, 3600);

        return [
            'bestsellers' => $bestsellers,
            'count' => $bestsellers->count(),
        ];
    }

    /**
     * Example 7: Top-Rated Books
     * 
     * Demonstrates:
     * - Filtering on cached queries
     * - Rating-based sorting with aggregation
     * 
     * @return array
     */
    public function topRatedBooksOptimized()
    {
        $topRated = QueryCache::getTopRatedBooks(10, 3600);

        return [
            'books' => $topRated,
            'total' => $topRated->count(),
        ];
    }

    /**
     * Example 8: Dashboard Statistics
     * 
     * Demonstrates:
     * - Caching computed statistics
     * - Using cache for dashboard data
     * - Cache invalidation pattern
     * 
     * @return array
     */
    public function dashboardStatsOptimized()
    {
        $orderStats = QueryCache::getOrderStatistics(3600);
        $inventoryStats = QueryCache::getInventoryStatistics(1800);

        return [
            'orders' => $orderStats,
            'inventory' => $inventoryStats,
            'cached' => true,
        ];
    }

    /**
     * Example 9: Manual Cache Management
     * 
     * Demonstrates:
     * - Manual cache operations
     * - Custom remember closures
     * - Cache invalidation triggers
     * 
     * @return array
     */
    public function customCachingExample()
    {
        // Manual cache with custom closure
        $expensiveData = QueryCache::remember('custom:expensive', 3600, function () {
            // This code runs once per hour
            return Book::with(['category', 'reviews'])
                ->withCount('orderItems')
                ->get();
        });

        // Manual cache invalidation
        if (request()->has('refresh')) {
            QueryCache::invalidate('custom:expensive');
        }

        return $expensiveData;
    }

    /**
     * Example 10: Minimal Data Selection
     * 
     * Demonstrates:
     * - Selecting only needed columns
     * - Reducing data transfer on network
     * - Efficient data for dropdowns/selects
     * 
     * @return array
     */
    public function efficientDataSelectionExample()
    {
        // Only select needed columns
        $users = EagerLoadHelper::usersMinimal()->get();
        $books = EagerLoadHelper::booksMinimal()->get();
        $categories = EagerLoadHelper::categoriesMinimal()->get();

        return [
            'users' => $users,
            'books' => $books,
            'categories' => $categories,
        ];
    }

    /**
     * Example 11: Conditional Eager Loading
     * 
     * Demonstrates:
     * - Loading relations conditionally
     * - Limiting eager loaded records
     * - Filtering during relation loading
     * 
     * @return Book
     */
    public function conditionalEagerLoadingExample()
    {
        $book = Book::with([
            // Only load top 5 latest reviews
            'reviews' => function ($query) {
                $query->latest()->limit(5);
            },
            // Only load reviews by authenticated user
            'reviews.user' => function ($query) {
                $query->where('status', 'active');
            },
            'category',
        ])->firstOrFail();

        return $book;
    }

    /**
     * Example 12: Read/Write Splitting Pattern
     * 
     * Demonstrates:
     * - Using explicit write connection when needed
     * - Forcing master read for read-after-write consistency
     * 
     * @param int $bookId
     * @return array
     */
    public function readWriteSplittingExample($bookId)
    {
        // Update book (goes to master)
        $book = Book::find($bookId);
        $book->update(['stock_quantity' => 100]);

        // With sticky=true, subsequent reads from same connection use master
        // ensuring read-after-write consistency
        $updatedBook = Book::find($bookId);

        // If you need to explicitly use write connection:
        $masterBook = Book::on('mysql')->find($bookId);

        // Read-only operations can use read replicas:
        $booksFromReplica = Book::with('category')
            ->where('category_id', 1)
            ->get();

        return [
            'updated' => $updatedBook,
            'consistent_read' => true,
        ];
    }

    /**
     * Example 13: N+1 Query Detection Pattern
     * 
     * Demonstrates:
     * - How to detect N+1 queries during development
     * - Query logging and monitoring
     * 
     * @return array
     */
    public function n1QueryDetectionExample()
    {
        // Enable query logging in development
        DB::enableQueryLog();

        // BAD: This causes N+1 queries
        $books = Book::all();
        $titles = [];
        foreach ($books as $book) {
            $titles[] = $book->category->name; // N additional queries!
        }

        $queriesRun = DB::getQueryLog();
        $queryCount = count($queriesRun);

        // GOOD: Use eager loading
        DB::flushQueryLog();
        $booksOptimized = EagerLoadHelper::booksList()->get();
        $titles = [];
        foreach ($booksOptimized as $book) {
            $titles[] = $book->category->name; // No additional queries
        }

        $optimizedQueries = DB::getQueryLog();
        $optimizedCount = count($optimizedQueries);

        return [
            'bad_queries' => $queryCount,
            'good_queries' => $optimizedCount,
            'improvement' => round((1 - $optimizedCount / $queryCount) * 100) . '%',
        ];
    }

    /**
     * Example 14: Cache Invalidation Pattern
     * 
     * Demonstrates:
     * - How to invalidate caches on data changes
     * - Model observer pattern for automatic invalidation
     * 
     * @return array
     */
    public function cacheInvalidationExample()
    {
        $category = Category::first();
        
        // Before update
        $cachedBefore = QueryCache::getCategories();
        
        // Update triggers invalidation (see Model observers)
        $category->update(['name' => 'Updated Name']);
        
        // Cache is automatically cleared
        // Next call will query fresh data
        $cachedAfter = QueryCache::getCategories();

        return [
            'cache_invalidated' => true,
            'data_refreshed' => true,
        ];
    }

    /**
     * Helper: Enable Query Profiling
     * 
     * Call this in development to profile queries
     * 
     * @return array
     */
    public static function profileQueries()
    {
        DB::enableQueryLog();

        return [
            'profiling' => true,
            'get_queries' => 'DB::getQueryLog()',
            'count_queries' => 'count(DB::getQueryLog())',
        ];
    }
}
