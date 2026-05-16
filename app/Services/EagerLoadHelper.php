<?php

namespace App\Services;

/**
 * EagerLoadHelper Service
 * 
 * Provides utilities and conventions for eager loading relationships
 * to prevent N+1 query problems in read-heavy operations.
 * 
 * Eager loading is critical when working with read replicas and
 * distributed queries to minimize connection overhead.
 * 
 * @package App\Services
 */
class EagerLoadHelper
{
    /**
     * Get books with all necessary relations for listing/display
     * 
     * Eager loads:
     * - category: for category name display
     * - reviews: for rating calculations
     * - orderItems: for sales count
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function booksList()
    {
        return \App\Models\Book::with([
            'category',
            'reviews',
            'orderItems'
        ]);
    }

    /**
     * Get books with relations optimized for detail views
     * 
     * Eager loads:
     * - category: full category details
     * - reviews: all user reviews with user info
     * - reviews.user: review authors
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function bookDetail()
    {
        return \App\Models\Book::with([
            'category',
            'reviews' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'reviews.user',
        ]);
    }

    /**
     * Get orders with all line item details
     * 
     * Eager loads:
     * - user: order customer
     * - orderItems: line items
     * - orderItems.book: product details for each item
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function ordersList()
    {
        return \App\Models\Order::with([
            'user',
            'orderItems.book'
        ]);
    }

    /**
     * Get orders for export/analytics
     * 
     * Eager loads relations needed for order reports and exports:
     * - user: customer details
     * - orderItems: all items
     * - orderItems.book: product information
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function ordersForExport()
    {
        return \App\Models\Order::with([
            'user:id,first_name,last_name,email,phone',
            'orderItems:id,order_id,book_id,quantity,price',
            'orderItems.book:id,isbn,title,author,price'
        ]);
    }

    /**
     * Get categories with book counts and featured books
     * 
     * Eager loads:
     * - books: products in category
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function categoriesWithBooks()
    {
        return \App\Models\Category::with([
            'books'
        ])->withCount('books');
    }

    /**
     * Get user with their orders and reviews
     * 
     * Eager loads:
     * - orders: user's purchase history
     * - reviews: user's product reviews
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function userProfile()
    {
        return \App\Models\User::with([
            'orders' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'orders.orderItems.book',
            'reviews' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'reviews.book'
        ]);
    }

    /**
     * Get audit logs with relation context for detailed audit trail
     * 
     * Eager loads:
     * - user: who made the change
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function auditLogsWithContext()
    {
        return \App\Models\AuditLog::with([
            'user:id,first_name,last_name,email'
        ])->latest();
    }

    /**
     * Get reviews with full context (user and book info)
     * 
     * Eager loads:
     * - user: reviewer details
     * - book: reviewed product
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function reviewsWithContext()
    {
        return \App\Models\Review::with([
            'user',
            'book'
        ])->latest();
    }

    /**
     * Get minimal user data (for dropdowns, selects)
     * 
     * Only selects necessary columns to reduce query size
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function usersMinimal()
    {
        return \App\Models\User::select([
            'id',
            'first_name',
            'last_name',
            'email'
        ]);
    }

    /**
     * Get books with minimal data (for selects, dropdowns)
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function booksMinimal()
    {
        return \App\Models\Book::select([
            'id',
            'title',
            'author',
            'isbn',
            'price',
            'category_id'
        ])->with('category:id,name');
    }

    /**
     * Get categories minimal (for selects, dropdowns)
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function categoriesMinimal()
    {
        return \App\Models\Category::select([
            'id',
            'name'
        ]);
    }

    /**
     * Example: Building dynamic eager load arrays
     * 
     * Usage in controllers:
     * 
     * ```php
     * $relationships = [
     *     'category',
     *     'reviews' => function($query) {
     *         $query->limit(5);
     *     }
     * ];
     * 
     * $book = Book::with($relationships)->find($id);
     * ```
     * 
     * @return void
     */
    public static function documentation()
    {
        return <<<'DOCS'
        # Eager Loading Best Practices

        ## Why Eager Loading Matters with Read/Write Splitting

        When using read replicas:
        1. Each lazy-loaded relation creates a new READ query to replica
        2. N+1 queries multiply round-trip latency
        3. Connection pooling overhead is multiplied

        Example N+1 Problem (BAD):
        ```php
        $books = Book::all(); // 1 query
        foreach ($books as $book) {
            echo $book->category->name; // N additional queries (N+1 problem)
        }
        ```

        Solution with Eager Loading (GOOD):
        ```php
        $books = Book::with('category')->get(); // 2 queries total
        foreach ($books as $book) {
            echo $book->category->name; // Already loaded, no queries
        }
        ```

        ## Common Eager Load Patterns

        ### 1. Basic Eager Loading
        Load a single relation:
        ```php
        $books = Book::with('category')->get();
        $books = Book::with(['category'])->get();
        ```

        ### 2. Multiple Relations
        Load several relations:
        ```php
        $books = Book::with(['category', 'reviews', 'author'])->get();
        ```

        ### 3. Nested Relations (Multi-level)
        Load relations of relations:
        ```php
        $orders = Order::with(['user', 'orderItems.book', 'orderItems.book.category'])->get();
        ```

        ### 4. Conditional Eager Loading
        Load relations based on conditions:
        ```php
        $books = Book::with(['category', 'reviews' => function($query) {
            $query->where('rating', '>=', 4);
        }])->get();
        ```

        ### 5. Limiting Eager Loaded Results
        ```php
        $books = Book::with(['reviews' => function($query) {
            $query->latest()->limit(5);
        }])->get();
        ```

        ### 6. Only Select Needed Columns
        Reduce data transfer on read queries:
        ```php
        $books = Book::with(['user:id,first_name,last_name'])->get();
        // Selects only needed columns from related table
        ```

        ## Integration with QueryCache

        Combine eager loading with query caching:
        ```php
        $categories = QueryCache::remember('categories:with_books', 3600, function() {
            return Category::with('books')->get();
        });
        ```

        ## Performance Monitoring

        Use Laravel Debugbar to identify N+1 queries:
        - Check "Queries" tab to see query count
        - Look for repeated queries with different IDs
        - Refactor to use eager loading

        ## Lazy vs Eager Loading Decision Matrix

        | Situation | Use |
        |-----------|-----|
        | Loading all related records | Eager Loading |
        | Loading 1-2 records | Lazy Loading OK |
        | In a loop (collection) | Eager Loading |
        | Optional relation (maybe null) | Lazy Loading |
        | Performance-critical query | Eager Loading |
        | Report/Export queries | Eager Loading |
        | API endpoints | Eager Loading |
        DOCS;
    }
}
