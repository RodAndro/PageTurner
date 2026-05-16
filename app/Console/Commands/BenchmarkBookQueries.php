<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Book;
use App\Models\Category;

class BenchmarkBookQueries extends Command
{
    protected $signature = 'benchmark:queries {iterations=100}';
    protected $description = 'Benchmark book query performance with configurable iterations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $iterations = (int) $this->argument('iterations');
        
        $this->info('Starting book query benchmarking', [
            'iterations' => $iterations,
            'timestamp' => now()->toIso8601String(),
        ]);

        $results = [
            'isbn_lookup' => $this->benchmarkIsbnLookup($iterations),
            'catalog_listing' => $this->benchmarkCatalogListing($iterations),
            'category_filter' => $this->benchmarkCategoryFilter($iterations),
            'fulltext_search' => $this->benchmarkFullTextSearch($iterations),
            'export_performance' => $this->benchmarkExportPerformance($iterations),
        ];

        $this->displayResults($results);
        
        Log::info('Book query benchmarking completed', [
            'iterations' => $iterations,
            'results' => $results,
        ]);

        return 0;
    }

    /**
     * Benchmark ISBN lookup performance
     */
    protected function benchmarkIsbnLookup(int $iterations): array
    {
        $this->info('Benchmarking ISBN lookup performance', ['iterations' => $iterations]);
        
        $times = [];
        $testIsbn = '9780743273565';
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            $book = Book::where('isbn', $testIsbn)->first(['id', 'title', 'author']);
            
            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $under50ms = count(array_filter($times, fn($time) => $time < 50));

        return [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'under_50ms_count' => $under50ms,
            'under_50ms_percentage' => round(($under50ms / $iterations) * 100, 2),
            'target_met' => $avgTime < 50,
            'status' => $avgTime < 50 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark catalog listing performance
     */
    protected function benchmarkCatalogListing(int $iterations): array
    {
        $this->info('Benchmarking catalog listing performance', ['iterations' => $iterations]);
        
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            $books = Book::select([
                    'books.id', 'books.isbn', 'books.title', 'books.author',
                    'books.price', 'books.stock_quantity', 'books.published_at',
                    'books.category_id'
                ])
                ->with(['category:id,name'])
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();
            
            $end = microtime(true);
            $times[] = ($end - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $under100ms = count(array_filter($times, fn($time) => $time < 100));

        return [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'under_100ms_count' => $under100ms,
            'under_100ms_percentage' => round(($under100ms / $iterations) * 100, 2),
            'target_met' => $avgTime < 100,
            'status' => $avgTime < 100 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark category filter performance
     */
    protected function benchmarkCategoryFilter(int $iterations): array
    {
        $this->info('Benchmarking category filter performance', ['iterations' => $iterations]);
        
        $categoryId = Category::first()->id;
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            $books = Book::select([
                    'books.id', 'books.title', 'books.author', 'books.price'
                ])
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();
            
            $end = microtime(true);
            $times[] = ($end - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $under150ms = count(array_filter($times, fn($time) => $time < 150));

        return [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'under_150ms_count' => $under150ms,
            'under_150ms_percentage' => round(($under150ms / $iterations) * 100, 2),
            'target_met' => $avgTime < 150,
            'status' => $avgTime < 150 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark full-text search performance
     */
    protected function benchmarkFullTextSearch(int $iterations): array
    {
        $this->info('Benchmarking full-text search performance', ['iterations' => $iterations]);
        
        $times = [];
        $searchTerm = 'great gatsby';
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            $books = Book::select([
                    'books.id', 'books.title', 'books.author', 'books.price'
                ])
                ->whereRaw('MATCH(title, description) AGAINST (?)', [$searchTerm])
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->limit(50)
                ->get();
            
            $end = microtime(true);
            $times[] = ($end - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $under300ms = count(array_filter($times, fn($time) => $time < 300));

        return [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'under_300ms_count' => $under300ms,
            'under_300ms_percentage' => round(($under300ms / $iterations) * 100, 2),
            'target_met' => $avgTime < 300,
            'status' => $avgTime < 300 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Benchmark export performance
     */
    protected function benchmarkExportPerformance(int $iterations): array
    {
        $this->info('Benchmarking export performance', ['iterations' => $iterations]);
        
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Simulate export processing
            $books = Book::select([
                    'books.id', 'books.isbn', 'books.title', 'books.author',
                    'books.price', 'books.description', 'books.category_id',
                    'books.publication_date', 'books.language', 'books.format',
                    'books.page_count', 'books.rating', 'books.stock_quantity',
                    'books.cover_image', 'books.created_at', 'books.updated_at'
                ])
                ->where('is_active', true)
                ->orderBy('id')
                ->take(10000)
                ->get();
            
            // Simulate processing time
            $processingTime = rand(20000, 30000) / 1000; // 20-30 seconds
            
            $end = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $times[] = ($end - $start) * 1000;
            
            // Force garbage collection
            if (($endMemory - $startMemory) > 100 * 1024 * 1024) { // 100MB threshold
                gc_collect_cycles();
            }
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $under30s = count(array_filter($times, fn($time) => $time < 30000)); // 30 seconds

        return [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'under_30s_count' => $under30s,
            'under_30s_percentage' => round(($under30s / $iterations) * 100, 2),
            'target_met' => $avgTime < 30000,
            'status' => $avgTime < 30000 ? 'PASS' : 'FAIL',
        ];
    }

    /**
     * Display benchmark results in a formatted table
     */
    protected function displayResults(array $results): void
    {
        $this->info("\n" . str_repeat('=', 80));
        $this->info('BOOK QUERY PERFORMANCE BENCHMARK RESULTS');
        $this->info(str_repeat('=', 80));
        
        foreach ($results as $testName => $result) {
            $status = $result['status'];
            $statusIcon = $status === 'PASS' ? '✅' : '❌';
            
            $this->info(sprintf(
                "%-20s %-20s %-10s %-10s %-10s %-10s %-10s",
                $testName,
                $result['avg_time_ms'] . 'ms',
                $result['target_met'] ? 'YES' : 'NO',
                $result['status'],
                $statusIcon
            ));
            
            if (isset($result['under_50ms_count'])) {
                $this->info(sprintf(
                    "%-20s %-20s %-10s %-10s %-10s %-10s %-10s",
                    '',
                    'Under 50ms:',
                    $result['under_50ms_count'],
                    '(' . $result['under_50ms_percentage'] . '%)',
                    ''
                ));
            }
            
            if (isset($result['under_100ms_count'])) {
                $this->info(sprintf(
                    "%-20s %-20s %-10s %-10s %-10s %-10s %-10s",
                    '',
                    'Under 100ms:',
                    $result['under_100ms_count'],
                    '(' . $result['under_100ms_percentage'] . '%)',
                    ''
                ));
            }
            
            if (isset($result['under_150ms_count'])) {
                $this->info(sprintf(
                    "%-20s %-20s %-10s %-10s %-10s %-10s %-10s",
                    '',
                    'Under 150ms:',
                    $result['under_150ms_count'],
                    '(' . $result['under_150ms_percentage'] . '%)',
                    ''
                ));
            }
            
            if (isset($result['under_300ms_count'])) {
                $this->info(sprintf(
                    "%-20s %-20s %-10s %-10s %-10s %-10s %-10s",
                    '',
                    'Under 30s:',
                    $result['under_30s_count'],
                    '(' . $result['under_30s_percentage'] . '%)',
                    ''
                ));
            }
            
            $this->info(str_repeat('-', 80));
        }
        
        $this->info(str_repeat('=', 80));
        $this->info('SUMMARY');
        $this->info(str_repeat('=', 80));
        
        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($result) => $result['status'] === 'PASS'));
        $overallStatus = $passedTests === $totalTests ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED';
        
        $this->info(sprintf("%-20s %-20s %-20s", 'Overall Status:', $overallStatus));
        $this->info(sprintf("%-20s %-20s %-20s", 'Tests Passed:', $passedTests . '/' . $totalTests));
        $this->info(sprintf("%-20s %-20s %-20s", 'Tests Failed:', ($totalTests - $passedTests) . '/' . $totalTests));
        $this->info(str_repeat('=', 80));
    }
}
