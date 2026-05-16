<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Services\RedisQueryCache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizedCatalogController extends Controller
{
    protected RedisQueryCache $cache;
    protected int $defaultLimit = 100;
    protected int $maxLimit = 1000;

    public function __construct(RedisQueryCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Optimized catalog listing with cursor pagination
     * Target: <100ms response time
     */
    public function index(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validated = $request->validate([
            'cursor' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:' . $this->maxLimit,
            'category' => 'nullable|string',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:title,author,price,publication_date,rating,created_at',
            'order' => 'nullable|in:asc,desc',
            'search' => 'nullable|string|max:255',
        ]);

        $limit = min($validated['limit'] ?? $this->defaultLimit, $this->maxLimit);
        $cacheTtl = $this->getCacheTtl($validated);

        return $this->cache->rememberCatalog(
            $validated['category'] ?? 'all',
            $validated['cursor'] ?? 'start',
            $limit,
            function () use ($validated, $limit) {
                return $this->performOptimizedQuery($validated, $limit);
            },
            $cacheTtl
        );
    }

    /**
     * Optimized ISBN exact search
     * Target: <50ms response time
     */
    public function findByIsbn(Request $request, string $isbn): JsonResponse
    {
        $startTime = microtime(true);
        
        // Validate ISBN format
        if (!$this->isValidIsbn($isbn)) {
            return response()->json([
                'error' => 'Invalid ISBN format',
                'message' => 'ISBN must be 10 or 13 digits'
            ], 400);
        }

        $cacheKey = "isbn:{$isbn}";
        
        $result = $this->cache->remember(
            $cacheKey,
            function () use ($isbn) {
                return $this->performIsbnSearch($isbn);
            },
            3600, // 1 hour cache for ISBN searches
            ['isbn', 'book']
        );

        $responseTime = (microtime(true) - $startTime) * 1000;
        
        Log::info('ISBN search completed', [
            'isbn' => $isbn,
            'found' => !empty($result),
            'response_time_ms' => round($responseTime, 2),
        ]);

        return response()->json([
            'data' => $result,
            'meta' => [
                'response_time_ms' => round($responseTime, 2),
                'cached' => true,
            ],
        ]);
    }

    /**
     * Optimized category filter
     * Target: <150ms for 100K+ results
     */
    public function findByCategory(Request $request, string $category): JsonResponse
    {
        $startTime = microtime(true);
        
        $validated = $request->validate([
            'cursor' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:' . $this->maxLimit,
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:title,author,price,publication_date,rating',
            'order' => 'nullable|in:asc,desc',
        ]);

        $limit = min($validated['limit'] ?? $this->defaultLimit, $this->maxLimit);
        $cacheTtl = $this->getCacheTtl($validated);

        $result = $this->cache->rememberCatalog(
            $category,
            $validated['cursor'] ?? 'start',
            $limit,
            function () use ($category, $validated, $limit) {
                return $this->performCategorySearch($category, $validated, $limit);
            },
            $cacheTtl
        );

        $responseTime = (microtime(true) - $startTime) * 1000;
        
        Log::info('Category search completed', [
            'category' => $category,
            'results_count' => count($result['data']),
            'response_time_ms' => round($responseTime, 2),
        ]);

        return response()->json([
            'data' => $result['data'],
            'meta' => array_merge($result['meta'], [
                'response_time_ms' => round($responseTime, 2),
                'cached' => true,
            ]),
        ]);
    }

    /**
     * Full-text search with Scout
     * Target: <300ms for 1M records
     */
    public function search(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:255',
            'cursor' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:' . $this->maxLimit,
            'category' => 'nullable|string',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:relevance,title,author,price,publication_date,rating',
            'order' => 'nullable|in:asc,desc',
        ]);

        $limit = min($validated['limit'] ?? $this->defaultLimit, $this->maxLimit);
        $cacheTtl = 300; // 5 minutes for search results

        $result = $this->cache->rememberSearch(
            $validated['q'],
            $validated,
            function () use ($validated, $limit) {
                return $this->performFullTextSearch($validated, $limit);
            },
            $cacheTtl
        );

        $responseTime = (microtime(true) - $startTime) * 1000;
        
        Log::info('Full-text search completed', [
            'query' => $validated['q'],
            'results_count' => $result['hits'],
            'response_time_ms' => round($responseTime, 2),
        ]);

        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'query' => $validated['q'],
                'hits' => $result['hits'],
                'search_time_ms' => round($responseTime, 2),
                'cached' => false, // Search results are typically not cached
            ],
        ]);
    }

    /**
     * Perform optimized catalog query with cursor pagination
     */
    protected function performOptimizedQuery(array $filters, int $limit): array
    {
        $query = DB::table('books')
            ->select([
                'id',
                'title',
                'author',
                'isbn',
                'price',
                'description',
                'category_id',
                'publication_date',
                'rating',
                'stock_quantity',
                'cover_image',
                'language',
                'format',
                'publisher',
            ])
            ->where('stock_quantity', '>', 0);

        // Apply filters
        if (!empty($filters['category'])) {
            $query->whereExists(function ($subQuery) use ($filters) {
                $subQuery->select(DB::raw(1))
                    ->from('categories')
                    ->whereColumn('categories.id', 'books.category_id')
                    ->where('categories.name', $filters['category']);
            });
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($subQuery) use ($filters) {
                $subQuery->where('title', 'LIKE', '%' . $filters['search'] . '%')
                       ->orWhere('author', 'LIKE', '%' . $filters['search'] . '%')
                       ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // Apply sorting with covering index optimization
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        switch ($sortField) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'author':
                $query->orderBy('author', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'publication_date':
                $query->orderBy('publication_date', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('rating', $sortOrder);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        // Cursor pagination implementation
        if (!empty($filters['cursor']) && $filters['cursor'] !== 'start') {
            $query->where('id', '>', $filters['cursor']);
        }

        $books = $query->limit($limit + 1)->get();

        // Determine if there are more results
        $hasMore = $books->count() > $limit;
        if ($hasMore) {
            $books = $books->take($limit);
        }

        // Get next cursor
        $nextCursor = $hasMore ? $books->last()->id : null;

        return [
            'data' => $books->toArray(),
            'meta' => [
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'limit' => $limit,
            ],
        ];
    }

    /**
     * Perform optimized ISBN search with index
     */
    protected function performIsbnSearch(string $isbn): ?array
    {
        // Use direct index lookup for exact ISBN match
        $book = DB::table('books')
            ->select([
                'id',
                'title',
                'author',
                'isbn',
                'price',
                'description',
                'category_id',
                'publication_date',
                'rating',
                'stock_quantity',
                'cover_image',
                'language',
                'format',
                'publisher',
            ])
            ->where('isbn', $isbn)
            ->first();

        return $book ? $book->toArray() : null;
    }

    /**
     * Perform optimized category search
     */
    protected function performCategorySearch(string $category, array $filters, int $limit): array
    {
        $query = DB::table('books')
            ->select([
                'id',
                'title',
                'author',
                'isbn',
                'price',
                'description',
                'category_id',
                'publication_date',
                'rating',
                'stock_quantity',
                'cover_image',
                'language',
                'format',
                'publisher',
            ])
            ->whereExists(function ($subQuery) use ($category) {
                $subQuery->select(DB::raw(1))
                    ->from('categories')
                    ->whereColumn('categories.id', 'books.category_id')
                    ->where('categories.name', $category);
            })
            ->where('stock_quantity', '>', 0);

        // Apply additional filters
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        switch ($sortField) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'author':
                $query->orderBy('author', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'publication_date':
                $query->orderBy('publication_date', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('rating', $sortOrder);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        // Cursor pagination
        if (!empty($filters['cursor']) && $filters['cursor'] !== 'start') {
            $query->where('id', '>', $filters['cursor']);
        }

        $books = $query->limit($limit + 1)->get();

        $hasMore = $books->count() > $limit;
        if ($hasMore) {
            $books = $books->take($limit);
        }

        $nextCursor = $hasMore ? $books->last()->id : null;

        return [
            'data' => $books->toArray(),
            'meta' => [
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'limit' => $limit,
                'category' => $category,
            ],
        ];
    }

    /**
     * Perform full-text search using Scout
     */
    protected function performFullTextSearch(array $filters, int $limit): array
    {
        $searchQuery = Book::search($filters['q']);

        // Apply filters to search
        if (!empty($filters['category'])) {
            $searchQuery->where('category', $filters['category']);
        }

        if (!empty($filters['min_price'])) {
            $searchQuery->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $searchQuery->where('price', '<=', $filters['max_price']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'relevance';
        $sortOrder = $filters['order'] ?? 'desc';

        switch ($sortField) {
            case 'title':
                $searchQuery->orderBy('title', $sortOrder);
                break;
            case 'author':
                $searchQuery->orderBy('author', $sortOrder);
                break;
            case 'price':
                $searchQuery->orderBy('price', $sortOrder);
                break;
            case 'publication_date':
                $searchQuery->orderBy('publication_date', $sortOrder);
                break;
            case 'rating':
                $searchQuery->orderBy('rating', $sortOrder);
                break;
            case 'relevance':
            default:
                // Scout handles relevance sorting by default
                break;
        }

        // Get total hits for pagination
        $hits = $searchQuery->get()->count();

        // Apply cursor pagination if provided
        if (!empty($filters['cursor']) && $filters['cursor'] !== 'start') {
            $searchQuery->where('id', '>', $filters['cursor']);
        }

        $books = $searchQuery->take($limit + 1)->get();

        $hasMore = $books->count() > $limit;
        if ($hasMore) {
            $books = $books->take($limit);
        }

        $nextCursor = $hasMore ? $books->last()->id : null;

        return [
            'data' => $books->toArray(),
            'hits' => $hits,
            'meta' => [
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'limit' => $limit,
            ],
        ];
    }

    /**
     * Validate ISBN format
     */
    protected function isValidIsbn(string $isbn): bool
    {
        // Remove hyphens and spaces
        $cleanIsbn = str_replace(['-', ' '], '', $isbn);
        
        // Check if it's numeric and 10 or 13 digits
        return is_numeric($cleanIsbn) && 
               in_array(strlen($cleanIsbn), [10, 13]);
    }

    /**
     * Get cache TTL based on filters
     */
    protected function getCacheTtl(array $filters): int
    {
        // Shorter TTL for searches, longer for static catalogs
        if (!empty($filters['search'])) {
            return 300; // 5 minutes
        }
        
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            return 600; // 10 minutes
        }
        
        return 1800; // 30 minutes for static catalogs
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): JsonResponse
    {
        $metrics = [
            'cache' => $this->cache->getStatistics(),
            'database' => [
                'connection_pool' => $this->getDatabaseConnectionPoolStats(),
                'query_performance' => $this->getQueryPerformanceStats(),
            ],
            'search' => [
                'index_stats' => $this->getSearchIndexStats(),
                'search_performance' => $this->getSearchPerformanceStats(),
            ],
        ];

        return response()->json($metrics);
    }

    /**
     * Get database connection pool statistics
     */
    protected function getDatabaseConnectionPoolStats(): array
    {
        return [
            'active_connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
            'max_connections' => DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 0,
            'connection_utilization' => $this->calculateConnectionUtilization(),
        ];
    }

    /**
     * Get query performance statistics
     */
    protected function getQueryPerformanceStats(): array
    {
        return [
            'slow_queries' => DB::select('SHOW STATUS LIKE "Slow_queries"')[0]->Value ?? 0,
            'queries_per_second' => DB::select('SHOW STATUS LIKE "Queries"')[0]->Value ?? 0,
            'avg_query_time' => DB::select('SHOW STATUS LIKE "Query_time"')[0]->Value ?? 0,
        ];
    }

    /**
     * Get search index statistics
     */
    protected function getSearchIndexStats(): array
    {
        return [
            'total_documents' => Book::count(),
            'indexed_documents' => Book::searchable()->count(),
            'index_size' => $this->getSearchIndexSize(),
            'last_indexed' => Book::max('updated_at'),
        ];
    }

    /**
     * Get search performance statistics
     */
    protected function getSearchPerformanceStats(): array
    {
        return [
            'avg_search_time' => $this->getAverageSearchTime(),
            'searches_per_minute' => $this->getSearchesPerMinute(),
            'cache_hit_rate' => $this->cache->getHitRatio(),
        ];
    }

    /**
     * Calculate connection utilization
     */
    protected function calculateConnectionUtilization(): float
    {
        $active = DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
        $max = DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 1;
        
        return $max > 0 ? round(($active / $max) * 100, 2) : 0.0;
    }

    /**
     * Get search index size (implementation depends on search driver)
     */
    protected function getSearchIndexSize(): string
    {
        $driver = config('scout.driver');
        
        switch ($driver) {
            case 'algolia':
                return 'N/A (Algolia)';
            case 'elasticsearch':
                return $this->getElasticsearchIndexSize();
            case 'database':
            default:
                return $this->getDatabaseIndexSize();
        }
    }

    /**
     * Get Elasticsearch index size
     */
    protected function getElasticsearchIndexSize(): string
    {
        // This would require Elasticsearch client
        return 'N/A (Elasticsearch)';
    }

    /**
     * Get database index size
     */
    protected function getDatabaseIndexSize(): string
    {
        try {
            $indexSize = DB::select("
                SELECT 
                    SUM(data_length + index_length) / 1024 / 1024 as size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'scout_index'
            ")[0]->size_mb ?? 0;
            
            return round($indexSize, 2) . ' MB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get average search time
     */
    protected function getAverageSearchTime(): float
    {
        // This would require analytics tracking
        return 0.0;
    }

    /**
     * Get searches per minute
     */
    protected function getSearchesPerMinute(): float
    {
        // This would require analytics tracking
        return 0.0;
    }
}
