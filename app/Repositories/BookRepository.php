<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\CursorPaginator;

class BookRepository
{
    /**
     * Get optimized catalog with cursor pagination
     * Target: <100ms response time
     */
    public function getOptimizedCatalog(array $filters = [], int $perPage = 100): CursorPaginator
    {
        // Select only required columns for covering index
        $query = Book::select([
            'books.id', 'books.isbn', 'books.title', 'books.author', 
            'books.price', 'books.stock_quantity', 'books.published_at', 'books.category_id'
        ]);

        // Apply filters efficiently
        if (!empty($filters['category_id'])) {
            $query->where('books.category_id', $filters['category_id']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('books.price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('books.price', '<=', $filters['max_price']);
        }

        if (!empty($filters['is_active'])) {
            $query->where('books.is_active', $filters['is_active']);
        }

        // Use covering index for sorting
        return $query->orderBy('books.published_at', 'desc')
            ->orderBy('books.id', 'desc') // Secondary sort for stable pagination
            ->cursorPaginate($perPage);
    }

    /**
     * Get books by exact ISBN with index optimization
     * Target: <50ms response time
     */
    public function findByIsbn(string $isbn): ?Book
    {
        // Use index-only scan for exact ISBN lookup
        return Book::select(['books.id', 'books.title', 'books.author', 'books.price'])
            ->where('books.isbn', $isbn)
            ->first();
    }

    /**
     * Get books by category with composite index optimization
     * Target: <150ms for 100K+ results
     */
    public function getByCategory(int $categoryId, array $options = []): CursorPaginator
    {
        $perPage = $options['per_page'] ?? 100;
        
        // Use composite index (category_id, published_at, is_active)
        return Book::select([
            'books.id', 'books.isbn', 'books.title', 'books.author', 
            'books.price', 'books.stock_quantity', 'books.published_at'
        ])
            ->where('books.category_id', $categoryId)
            ->where('books.is_active', true)
            ->orderBy('books.published_at', 'desc')
            ->orderBy('books.id', 'desc')
            ->cursorPaginate($perPage);
    }

    /**
     * Full-text search with optimized caching
     * Target: <300ms for 1M records
     */
    public function searchBooks(string $query, array $options = []): CursorPaginator
    {
        $perPage = $options['per_page'] ?? 100;
        $cacheKey = "search_books:" . md5($query . serialize($options));
        
        return Cache::remember($cacheKey, 300, function () use ($query, $options, $perPage) {
            // Use Scout for full-text search with fallback to database
            if (config('scout.driver') !== 'database') {
                return $this->scoutSearch($query, $options, $perPage);
            }
            
            // Fallback to database full-text search
            return $this->databaseFullTextSearch($query, $options, $perPage);
        });
    }

    /**
     * Scout-based search
     */
    protected function scoutSearch(string $query, array $options, int $perPage): CursorPaginator
    {
        $bookQuery = Book::search($query);
        
        // Apply additional filters to Scout search
        if (!empty($options['category_id'])) {
            $bookQuery->where('category_id', $options['category_id']);
        }

        if (!empty($options['min_price'])) {
            $bookQuery->where('price', '>=', $options['min_price']);
        }

        if (!empty($options['max_price'])) {
            $bookQuery->where('price', '<=', $options['max_price']);
        }

        return $bookQuery->orderBy('created_at', 'desc')
            ->cursorPaginate($perPage);
    }

    /**
     * Database full-text search fallback
     */
    protected function databaseFullTextSearch(string $query, array $options, int $perPage): CursorPaginator
    {
        return Book::select([
            'books.id', 'books.isbn', 'books.title', 'books.author', 
            'books.price', 'books.stock_quantity', 'books.published_at'
        ])
            ->whereRaw('MATCH(title, description) AGAINST (?)', [$query])
            ->where('books.is_active', true)
            ->orderBy('books.published_at', 'desc')
            ->orderBy('books.id', 'desc')
            ->cursorPaginate($perPage);
    }

    /**
     * Get bestsellers with materialized view optimization
     */
    public function getBestsellers(int $limit = 50): array
    {
        // Use materialized view for bestseller data
        return DB::table('bestsellers')
            ->select([
                'id', 'title', 'author', 'isbn', 'price', 'category_name',
                'total_sold', 'total_revenue', 'avg_rating', 'review_count',
                'popularity_score', 'best_seller_rank', 'sales_velocity'
            ])
            ->orderBy('popularity_score', 'desc')
            ->orderBy('best_seller_rank', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get books with eager loading optimization
     */
    public function getBooksWithRelations(array $bookIds): array
    {
        // Load only required relations to prevent N+1
        return Book::select(['books.id', 'books.title', 'books.author', 'books.price'])
            ->with(['category:id,name', 'reviews:rating']) // Only load what's needed
            ->whereIn('books.id', $bookIds)
            ->get()
            ->toArray();
    }

    /**
     * Get active books count with caching
     */
    public function getActiveBooksCount(): int
    {
        return Cache::remember('active_books_count', 3600, function () {
            return Book::where('is_active', true)->count();
        });
    }

    /**
     * Get category statistics with optimization
     */
    public function getCategoryStatistics(): array
    {
        return Cache::remember('category_statistics', 1800, function () {
            return DB::table('books')
                ->select([
                    'categories.name',
                    DB::raw('COUNT(*) as total_books'),
                    DB::raw('AVG(books.price) as avg_price'),
                    DB::raw('SUM(books.stock_quantity) as total_stock'),
                    DB::raw('COUNT(CASE WHEN books.is_active = 1 THEN 1 END) as active_books')
                ])
                ->join('categories', 'categories.id', '=', 'books.category_id')
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('total_books', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get price range statistics with covering index
     */
    public function getPriceRangeStatistics(): array
    {
        return Cache::remember('price_range_statistics', 3600, function () {
            return DB::table('books')
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('MIN(price) as min_price'),
                    DB::raw('MAX(price) as max_price'),
                    DB::raw('AVG(price) as avg_price'),
                    DB::raw('PERCENTILE_CONTINUOUS(0.25, WITHIN GROUP ORDER BY price) as q1_price'),
                    DB::raw('PERCENTILE_CONTINUOUS(0.50, WITHIN GROUP ORDER BY price) as median_price'),
                    DB::raw('PERCENTILE_CONTINUOUS(0.75, WITHIN GROUP ORDER BY price) as q3_price'),
                ])
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Invalidate relevant caches
     */
    public function invalidateBookCaches(?int $categoryId = null): void
    {
        $tags = ['books'];
        
        if ($categoryId) {
            $tags[] = "category:{$categoryId}";
        }
        
        Cache::tags($tags)->flush();
    }

    /**
     * Bulk update books with optimization
     */
    public function bulkUpdate(array $updates): int
    {
        $affected = 0;
        
        // Process in chunks to avoid memory issues
        collect($updates)->chunk(1000, function ($chunk) use (&$affected) {
            foreach ($chunk as $update) {
                $affected += Book::where('id', $update['id'])
                    ->update($update['data']);
            }
        });
        
        // Invalidate caches after bulk update
        $this->invalidateBookCaches();
        
        return $affected;
    }

    /**
     * Get books for export with streaming optimization
     */
    public function getBooksForExport(array $filters = []): \Generator
    {
        $query = Book::select([
            'books.id', 'books.isbn', 'books.title', 'books.author', 
            'books.price', 'books.description', 'books.category_id',
            'books.publisher', 'books.publication_date', 'books.language',
            'books.format', 'books.page_count', 'books.rating',
            'books.stock_quantity', 'books.cover_image'
        ]);

        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->where('books.category_id', $filters['category_id']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('books.price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('books.price', '<=', $filters['max_price']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('books.published_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('books.published_at', '<=', $filters['date_to']);
        }

        // Stream results for memory efficiency
        foreach ($query->cursor() as $book) {
            yield $book;
        }
    }

    /**
     * Get search suggestions with caching
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        $cacheKey = "search_suggestions:" . md5($query);
        
        return Cache::remember($cacheKey, 1800, function () use ($query, $limit) {
            // Use full-text search for suggestions
            return Book::select(['books.title', 'books.author'])
                ->whereRaw('MATCH(title) AGAINST (?)', [$query])
                ->where('books.is_active', true)
                ->limit($limit)
                ->get()
                ->pluck('title')
                ->toArray();
        });
    }

    /**
     * Get related books with optimization
     */
    public function getRelatedBooks(int $bookId, int $limit = 10): array
    {
        return Cache::remember("related_books:{$bookId}", 3600, function () use ($bookId, $limit) {
            $book = Book::find($bookId);
            
            if (!$book) {
                return [];
            }
            
            // Find books in same category with similar characteristics
            return Book::select(['books.id', 'books.title', 'books.author', 'books.price'])
                ->where('books.category_id', $book->category_id)
                ->where('books.id', '!=', $bookId)
                ->where('books.is_active', true)
                ->where(function ($query) use ($book) {
                    $query->where('books.price', '>=', $book->price * 0.8)
                           ->where('books.price', '<=', $book->price * 1.2);
                })
                ->orderByRaw('ABS(books.price - ?) ASC', [$book->price])
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }
}
