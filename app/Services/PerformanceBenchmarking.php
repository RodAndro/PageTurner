<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PerformanceBenchmarking
{
    protected array $benchmarks = [];
    protected array $metrics = [];
    protected float $startTime;
    protected int $memoryLimit = 512 * 1024 * 1024; // 512MB

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Benchmark catalog listing performance
     * Target: <100ms response time
     */
    public function benchmarkCatalogListing(int $limit = 100): array
    {
        $benchmarkName = 'catalog_listing';
        $this->startBenchmark($benchmarkName);

        // Simulate catalog query
        $query = DB::table('books')
            ->select(['id', 'title', 'author', 'price', 'category_id'])
            ->where('stock_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        $results = $query->get();
        
        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'limit' => $limit,
            'results_count' => $results->count(),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'target_met' => $executionTime < 0.100, // 100ms
            'status' => $executionTime < 0.100 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark ISBN exact search performance
     * Target: <50ms response time
     */
    public function benchmarkIsbnSearch(string $isbn): array
    {
        $benchmarkName = 'isbn_search';
        $this->startBenchmark($benchmarkName);

        // Simulate exact ISBN search
        $query = DB::table('books')
            ->where('isbn', $isbn)
            ->first();

        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'isbn' => $isbn,
            'found' => !is_null($query),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'target_met' => $executionTime < 0.050, // 50ms
            'status' => $executionTime < 0.050 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark category filter performance
     * Target: <150ms for 100K+ results
     */
    public function benchmarkCategoryFilter(string $category, int $expectedResults = 100000): array
    {
        $benchmarkName = 'category_filter';
        $this->startBenchmark($benchmarkName);

        // Simulate category filter query
        $query = DB::table('books')
            ->select(['id', 'title', 'author', 'price'])
            ->whereExists(function ($subQuery) use ($category) {
                $subQuery->select(DB::raw(1))
                    ->from('categories')
                    ->whereColumn('categories.id', 'books.category_id')
                    ->where('categories.name', $category);
            })
            ->orderBy('created_at', 'desc');

        $results = $query->get();
        
        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'category' => $category,
            'results_count' => $results->count(),
            'expected_results' => $expectedResults,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'target_met' => $executionTime < 0.150, // 150ms
            'status' => $executionTime < 0.150 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark full-text search performance
     * Target: <300ms for 1M records
     */
    public function benchmarkFullTextSearch(string $searchTerm): array
    {
        $benchmarkName = 'full_text_search';
        $this->startBenchmark($benchmarkName);

        // Simulate full-text search
        $query = DB::table('books')
            ->select(['id', 'title', 'author', 'description'])
            ->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('title', 'LIKE', '%' . $searchTerm . '%')
                       ->orWhere('author', 'LIKE', '%' . $searchTerm . '%')
                       ->orWhere('description', 'LIKE', '%' . $searchTerm . '%');
            })
            ->orderBy('created_at', 'desc')
            ->limit(1000); // Reasonable limit for search results

        $results = $query->get();
        
        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'search_term' => $searchTerm,
            'results_count' => $results->count(),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'target_met' => $executionTime < 0.300, // 300ms
            'status' => $executionTime < 0.300 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark streaming export performance
     * Target: <30s for 10K records
     */
    public function benchmarkStreamingExport(int $recordCount = 10000): array
    {
        $benchmarkName = 'streaming_export';
        $this->startBenchmark($benchmarkName);

        $processedCount = 0;
        $chunkSize = 1000;
        
        // Simulate streaming export
        for ($offset = 0; $offset < $recordCount; $offset += $chunkSize) {
            $chunk = DB::table('books')
                ->select(['id', 'title', 'author', 'isbn', 'price'])
                ->offset($offset)
                ->limit(min($chunkSize, $recordCount - $offset))
                ->get();
            
            $processedCount += $chunk->count();
            
            // Simulate processing delay
            usleep(1000); // 1ms per record
        }
        
        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'target_records' => $recordCount,
            'processed_records' => $processedCount,
            'execution_time_seconds' => round($executionTime, 2),
            'records_per_second' => round($processedCount / $executionTime, 2),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'target_met' => $executionTime < 30, // 30 seconds
            'status' => $executionTime < 30 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Run comprehensive performance suite
     */
    public function runPerformanceSuite(): array
    {
        Log::info('Starting performance benchmarking suite');
        
        $results = [
            'suite_start_time' => now()->toIso8601String(),
            'benchmarks' => [],
            'summary' => [],
        ];

        // Run all benchmarks
        $results['benchmarks'][] = $this->benchmarkCatalogListing();
        $results['benchmarks'][] = $this->benchmarkIsbnSearch('9780743273565');
        $results['benchmarks'][] = $this->benchmarkCategoryFilter('Fiction', 100000);
        $results['benchmarks'][] = $this->benchmarkFullTextSearch('Great Gatsby');
        $results['benchmarks'][] = $this->benchmarkStreamingExport();

        // Calculate summary
        $results['summary'] = $this->calculateSummary($results['benchmarks']);
        $results['suite_end_time'] = now()->toIso8601String();
        $results['total_suite_time'] = round(microtime(true) - $this->startTime, 2);

        Log::info('Performance benchmarking suite completed', [
            'summary' => $results['summary'],
            'total_time' => $results['total_suite_time'],
        ]);

        return $results;
    }

    /**
     * Benchmark database performance
     */
    public function benchmarkDatabasePerformance(): array
    {
        $benchmarkName = 'database_performance';
        $this->startBenchmark($benchmarkName);

        // Connection pool test
        $connectionTests = [];
        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTests[] = microtime(true) - $start;
        }

        // Query performance test
        $queryTests = [];
        $queries = [
            'SELECT COUNT(*) FROM books',
            'SELECT * FROM books WHERE price > 50 LIMIT 100',
            'SELECT category_id, COUNT(*) FROM books GROUP BY category_id',
        ];

        foreach ($queries as $query) {
            $start = microtime(true);
            DB::select($query);
            $queryTests[] = microtime(true) - $start;
        }

        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'connection_tests' => [
                'count' => count($connectionTests),
                'avg_time_ms' => round(array_sum($connectionTests) * 1000 / count($connectionTests), 2),
                'min_time_ms' => round(min($connectionTests) * 1000, 2),
                'max_time_ms' => round(max($connectionTests) * 1000, 2),
            ],
            'query_tests' => [
                'count' => count($queryTests),
                'avg_time_ms' => round(array_sum($queryTests) * 1000 / count($queryTests), 2),
                'min_time_ms' => round(min($queryTests) * 1000, 2),
                'max_time_ms' => round(max($queryTests) * 1000, 2),
            ],
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'execution_time_ms' => round($executionTime * 1000, 2),
        ];
    }

    /**
     * Benchmark cache performance
     */
    public function benchmarkCachePerformance(): array
    {
        $benchmarkName = 'cache_performance';
        $this->startBenchmark($benchmarkName);

        $cacheTests = [];
        $testKeys = ['book_1', 'book_2', 'book_3', 'book_4', 'book_5'];
        
        // Cache write test
        $writeStart = microtime(true);
        foreach ($testKeys as $key) {
            Cache::put($key, 'test_data_' . $key, 3600);
        }
        $writeTime = microtime(true) - $writeStart;

        // Cache read test
        $readTimes = [];
        foreach ($testKeys as $key) {
            $start = microtime(true);
            Cache::get($key);
            $readTimes[] = microtime(true) - $start;
        }

        $executionTime = $this->endBenchmark($benchmarkName);
        
        return [
            'benchmark' => $benchmarkName,
            'cache_writes' => [
                'count' => count($testKeys),
                'total_time_ms' => round($writeTime * 1000, 2),
                'avg_time_ms' => round($writeTime * 1000 / count($testKeys), 2),
            ],
            'cache_reads' => [
                'count' => count($readTimes),
                'avg_time_ms' => round(array_sum($readTimes) * 1000 / count($readTimes), 2),
                'min_time_ms' => round(min($readTimes) * 1000, 2),
                'max_time_ms' => round(max($readTimes) * 1000, 2),
            ],
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'execution_time_ms' => round($executionTime * 1000, 2),
        ];
    }

    /**
     * Start benchmark
     */
    protected function startBenchmark(string $name): void
    {
        $this->benchmarks[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
        
        Log::debug("Starting benchmark: {$name}");
    }

    /**
     * End benchmark and return execution time
     */
    protected function endBenchmark(string $name): float
    {
        $endTime = microtime(true);
        $startTime = $this->benchmarks[$name]['start_time'];
        
        $this->benchmarks[$name]['end_time'] = $endTime;
        $this->benchmarks[$name]['execution_time'] = $endTime - $startTime;
        $this->benchmarks[$name]['end_memory'] = memory_get_usage(true);
        
        $executionTime = $endTime - $startTime;
        
        Log::debug("Benchmark completed: {$name}", [
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_used_mb' => round((memory_get_usage(true) - $this->benchmarks[$name]['start_memory']) / 1024 / 1024, 2),
        ]);
        
        return $executionTime;
    }

    /**
     * Calculate benchmark summary
     */
    protected function calculateSummary(array $benchmarks): array
    {
        $totalBenchmarks = count($benchmarks);
        $passedBenchmarks = 0;
        $failedBenchmarks = 0;
        $totalExecutionTime = 0;
        $avgMemoryUsage = 0;

        foreach ($benchmarks as $benchmark) {
            if ($benchmark['status'] === 'PASS') {
                $passedBenchmarks++;
            } else {
                $failedBenchmarks++;
            }
            
            if (isset($benchmark['execution_time_ms'])) {
                $totalExecutionTime += $benchmark['execution_time_ms'];
            }
            
            if (isset($benchmark['memory_usage_mb'])) {
                $avgMemoryUsage += $benchmark['memory_usage_mb'];
            }
        }

        $avgExecutionTime = $totalExecutionTime / $totalBenchmarks;
        $avgMemoryUsage = $avgMemoryUsage / $totalBenchmarks;
        $passRate = ($passedBenchmarks / $totalBenchmarks) * 100;

        return [
            'total_benchmarks' => $totalBenchmarks,
            'passed_benchmarks' => $passedBenchmarks,
            'failed_benchmarks' => $failedBenchmarks,
            'pass_rate_percent' => round($passRate, 2),
            'avg_execution_time_ms' => round($avgExecutionTime, 2),
            'avg_memory_usage_mb' => round($avgMemoryUsage, 2),
            'overall_status' => $passRate >= 80 ? 'PASS' : 'FAIL', // 80% pass rate
            'recommendations' => $this->generateRecommendations($benchmarks),
        ];
    }

    /**
     * Generate performance recommendations
     */
    protected function generateRecommendations(array $benchmarks): array
    {
        $recommendations = [];

        foreach ($benchmarks as $benchmark) {
            if ($benchmark['status'] === 'FAIL') {
                switch ($benchmark['benchmark']) {
                    case 'catalog_listing':
                        $recommendations[] = 'Add covering index on (created_at, stock_quantity) for catalog queries';
                        break;
                    case 'isbn_search':
                        $recommendations[] = 'Ensure ISBN column has unique index for exact searches';
                        break;
                    case 'category_filter':
                        $recommendations[] = 'Create composite index on (category_id, created_at) for category filters';
                        break;
                    case 'full_text_search':
                        $recommendations[] = 'Implement FULLTEXT index on title, author, description columns';
                        $recommendations[] = 'Consider using Elasticsearch for better search performance';
                        break;
                    case 'streaming_export':
                        $recommendations[] = 'Increase chunk size for better throughput';
                        $recommendations[] = 'Implement parallel processing for large exports';
                        break;
                }
            }
            
            // Memory recommendations
            if (isset($benchmark['memory_usage_mb']) && $benchmark['memory_usage_mb'] > 100) {
                $recommendations[] = 'Optimize query memory usage with chunking';
                $recommendations[] = 'Consider increasing PHP memory limit for large operations';
            }
        }

        return array_unique($recommendations);
    }

    /**
     * Get system performance metrics
     */
    public function getSystemMetrics(): array
    {
        return [
            'memory' => [
                'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit_mb' => round($this->memoryLimit / 1024 / 1024, 2),
                'usage_percentage' => round((memory_get_usage(true) / $this->memoryLimit) * 100, 2),
            ],
            'database' => [
                'connections' => $this->getDatabaseConnections(),
                'slow_queries' => $this->getSlowQueriesCount(),
                'query_cache_hit_rate' => $this->getQueryCacheHitRate(),
            ],
            'cache' => [
                'redis_memory' => $this->getRedisMemoryUsage(),
                'redis_keys' => $this->getRedisKeyCount(),
                'hit_rate' => $this->getCacheHitRate(),
            ],
            'system' => [
                'load_average' => sys_getloadavg()[0] ?? 0,
                'cpu_usage' => $this->getCpuUsage(),
                'disk_usage' => $this->getDiskUsage(),
            ],
        ];
    }

    /**
     * Get database connection count
     */
    protected function getDatabaseConnections(): int
    {
        try {
            $result = DB::select('SHOW STATUS LIKE "Threads_connected"');
            return (int)($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get slow queries count
     */
    protected function getSlowQueriesCount(): int
    {
        try {
            $result = DB::select('SHOW STATUS LIKE "Slow_queries"');
            return (int)($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get query cache hit rate
     */
    protected function getQueryCacheHitRate(): float
    {
        try {
            $result = DB::select('SHOW STATUS LIKE "Qcache_hits"');
            $hits = (int)($result[0]->Value ?? 0);
            
            $result = DB::select('SHOW STATUS LIKE "Qcache_inserts"');
            $inserts = (int)($result[0]->Value ?? 0);
            
            $total = $hits + $inserts;
            return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get Redis memory usage
     */
    protected function getRedisMemoryUsage(): string
    {
        try {
            $redis = new \Redis();
            $info = $redis->info('memory');
            $used = $info['used_memory'] ?? 0;
            $max = $info['maxmemory'] ?? 0;
            
            return round($used / 1024 / 1024, 2) . 'MB / ' . round($max / 1024 / 1024, 2) . 'MB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get Redis key count
     */
    protected function getRedisKeyCount(): int
    {
        try {
            $redis = new \Redis();
            $info = $redis->info('keyspace');
            return (int)($info['keys'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hit rate
     */
    protected function getCacheHitRate(): float
    {
        try {
            $redis = new \Redis();
            $stats = $redis->info('stats');
            $hits = $stats['keyspace_hits'] ?? 0;
            $misses = $stats['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get CPU usage
     */
    protected function getCpuUsage(): float
    {
        // This would require system-specific implementation
        return 0.0; // Placeholder
    }

    /**
     * Get disk usage
     */
    protected function getDiskUsage(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    /**
     * Get all benchmark results
     */
    public function getBenchmarks(): array
    {
        return $this->benchmarks;
    }

    /**
     * Clear benchmark data
     */
    public function clearBenchmarks(): void
    {
        $this->benchmarks = [];
        $this->metrics = [];
    }
}
