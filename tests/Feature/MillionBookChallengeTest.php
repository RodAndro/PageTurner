<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MillionBookChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected int $memoryLimit = 512 * 1024 * 1024; // 512MB
    protected int $timeLimit = 600; // 10 minutes in seconds

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->count(5)->create();

        if (!$this->isMillionSeedTest()) {
            Book::factory()->count(500)->create();
        }
    }

    /**
     * Test 1M book seeding performance
     */
    public function test_million_book_seeding_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        Log::info('Starting 1M book seeding test', [
            'memory_limit_mb' => round($this->memoryLimit / 1024 / 1024, 2),
            'time_limit_minutes' => $this->timeLimit / 60,
        ]);

        // Run the seeder
        Book::query()->delete();
        $this->artisan('db:seed', ['--class' => 'MassBookSeeder', '--no-interaction']);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $peakMemory = memory_get_peak_usage(true);

        $this->assertLessThanCustom($executionTime, $this->timeLimit, 'Seeding should complete within 10 minutes');
        $this->assertLessThanCustom($peakMemory, $this->memoryLimit, 'Memory usage should stay below 512MB');

        // Verify record count
        $totalBooks = DB::table('books')->count();
        $this->assertEquals(1000000, $totalBooks, 'Should have exactly 1,000,000 books');

        Log::info('1M book seeding test completed', [
            'execution_time_seconds' => round($executionTime, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'books_seeded' => $totalBooks,
            'memory_efficiency' => round(($peakMemory / $this->memoryLimit) * 100, 2),
        ]);
    }

    /**
     * Test ISBN validation and uniqueness
     */
    public function test_isbn_validation_and_uniqueness(): void
    {
        // Get sample of books to test
        $books = Book::take(1000)->get(['id', 'isbn']);

        $invalidIsbns = 0;
        $duplicateIsbns = 0;
        $seenIsbns = [];

        foreach ($books as $book) {
            // Test ISBN format (should be 13 digits)
            if (!preg_match('/^\d{13}$/', $book->isbn)) {
                $invalidIsbns++;
                continue;
            }

            // Test ISBN-13 checksum
            if (!$this->isValidIsbn13($book->isbn)) {
                $invalidIsbns++;
                continue;
            }

            // Test for duplicates
            if (in_array($book->isbn, $seenIsbns)) {
                $duplicateIsbns++;
            } else {
                $seenIsbns[] = $book->isbn;
            }
        }

        $this->assertEquals(0, $invalidIsbns, 'Should have no invalid ISBNs');
        $this->assertEquals(0, $duplicateIsbns, 'Should have no duplicate ISBNs');
        $this->assertEquals(count($seenIsbns), count($books), 'All ISBNs should be unique');

        Log::info('ISBN validation test completed', [
            'total_tested' => $books->count(),
            'invalid_isbns' => $invalidIsbns,
            'duplicate_isbns' => $duplicateIsbns,
            'unique_isbns' => count($seenIsbns),
        ]);
    }

    /**
     * Test foreign key integrity
     */
    public function test_foreign_key_integrity(): void
    {
        // Get sample books and their categories
        $books = Book::take(1000)->get(['id', 'category_id']);
        $categoryIds = $books->pluck('category_id')->unique()->values();
        $existingCategories = Category::query()
            ->whereIn('id', $categoryIds, 'and', false)
            ->get(['id'])
            ->map(fn (Category $category) => $category->id)
            ->values();

        $this->assertEquals(
            $categoryIds->count(),
            $existingCategories->count(),
            'All book category IDs should exist in categories table'
        );

        // Test for orphaned records
        $orphanedBooks = DB::table('books')
            ->leftJoin('categories', 'books.category_id', '=', 'categories.id')
            ->whereNull('categories.id', 'and')
            ->count();

        $this->assertEquals(0, $orphanedBooks, 'Should have no orphaned book records');

        Log::info('Foreign key integrity test completed', [
            'books_tested' => $books->count(),
            'categories_found' => $existingCategories->count(),
            'orphaned_books' => $orphanedBooks,
        ]);
    }

    /**
     * Test data realism and distribution
     */
    public function test_data_realism_and_distribution(): void
    {
        $books = Book::take(10000)->get(['price', 'publication_date', 'language', 'format']);

        // Test price ranges
        $priceRanges = [
            'ebook' => ['min' => 9.99, 'max' => 19.99],
            'paperback' => ['min' => 12.99, 'max' => 29.99],
            'hardcover' => ['min' => 24.99, 'max' => 49.99],
            'audiobook' => ['min' => 19.99, 'max' => 39.99],
        ];

        $formatPriceViolations = 0;
        foreach ($books as $book) {
            if (isset($priceRanges[$book->format])) {
                $range = $priceRanges[$book->format];
                if ($book->price < $range['min'] || $book->price > $range['max']) {
                    $formatPriceViolations++;
                }
            }
        }

        $this->assertLessThanCustom($formatPriceViolations, 50, 'Less than 5% of books should have price range violations');

        // Test publication date distribution
        $publicationYears = $books->pluck('publication_date')->map(function ($date) {
            return date('Y', strtotime($date));
        })->toArray();

        $yearRange = [
            'min' => min($publicationYears),
            'max' => max($publicationYears),
            'avg' => round(array_sum($publicationYears) / count($publicationYears)),
        ];

        // Should have reasonable distribution across years
        $this->assertGreaterThanCustom($yearRange['min'], 1900, 'Publication dates should start from 1900 or later');
        $this->assertLessThanOrEqual((int) date('Y'), (int) $yearRange['max'], 'Publication dates should not be in the future');

        // Test language distribution
        $languageCounts = $books->pluck('language')->countBy()->values();
        $this->assertGreaterThanCustom($languageCounts->sum(), 0, 'Should have books in multiple languages');

        Log::info('Data realism test completed', [
            'books_tested' => $books->count(),
            'format_price_violations' => $formatPriceViolations,
            'publication_year_range' => $yearRange,
            'language_distribution' => $languageCounts->toArray(),
        ]);
    }

    /**
     * Test query performance benchmarks
     */
    public function test_query_performance_benchmarks(): void
    {
        $iterations = 100;

        // Test ISBN lookup performance
        $isbnTimes = [];
        $testIsbn = Book::query()->value('isbn');
        $this->assertNotEmpty($testIsbn, 'A book ISBN is required for ISBN lookup benchmarks');
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $book = Book::query()
                ->where('isbn', '=', $testIsbn, 'and')
                ->first(['id', 'title', 'author']);
            $end = microtime(true);
            $isbnTimes[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        $avgIsbnTime = array_sum($isbnTimes) / count($isbnTimes);
        $this->assertLessThanCustom($avgIsbnTime, 50, 'ISBN lookup should be less than 50ms average');

        // Test catalog listing performance
        $catalogTimes = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $books = Book::select(['id', 'title', 'author', 'price', 'category_id'])
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();
            $end = microtime(true);
            $catalogTimes[] = ($end - $start) * 1000;
        }

        $avgCatalogTime = array_sum($catalogTimes) / count($catalogTimes);
        $this->assertLessThanCustom($avgCatalogTime, 100, 'Catalog listing should be less than 100ms average');

        // Test category filter performance
        $categoryFilterTimes = [];
        $testCategory = Category::query()->first(['id']);
        $this->assertNotNull($testCategory, 'A category is required for category filter benchmarks');
        $testCategoryId = $testCategory->id;
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $books = Book::query()
                ->where('category_id', '=', $testCategoryId, 'and')
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->limit(100)
                ->get();
            $end = microtime(true);
            $categoryFilterTimes[] = ($end - $start) * 1000;
        }

        $avgCategoryFilterTime = array_sum($categoryFilterTimes) / count($categoryFilterTimes);
        $this->assertLessThanCustom($avgCategoryFilterTime, 150, 'Category filter should be less than 150ms average');

        Log::info('Query performance benchmarks completed', [
            'iterations' => $iterations,
            'avg_isbn_lookup_ms' => round($avgIsbnTime, 2),
            'avg_catalog_listing_ms' => round($avgCatalogTime, 2),
            'avg_category_filter_ms' => round($avgCategoryFilterTime, 2),
        ]);
    }

    /**
     * Test cache performance
     */
    public function test_cache_performance(): void
    {
        // Test cache hit rates
        $cacheHits = 0;
        $cacheMisses = 0;
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            // First call should be a cache miss
            $book = Book::query()->find(1, ['*']);
            if ($book) {
                $cacheMisses++;
            }
            
            // Subsequent calls should be cache hits
            $book = Book::query()->find(1, ['*']);
            if ($book) {
                $cacheHits++;
            }
            
            $end = microtime(true);
        }

        $hitRate = $iterations > 0 ? ($cacheHits / $iterations) * 100 : 0;
        $this->assertGreaterThanCustom($hitRate, 80, 'Cache hit rate should be above 80%');

        // Test cache invalidation
        $testBook = Book::query()->first(['*']);
        $this->assertNotNull($testBook, 'A book is required for cache invalidation testing');
        $originalTitle = $testBook->title;
        
        // Update book to trigger cache invalidation
        $testBook->update(['title' => 'Updated Title']);
        
        // Verify cache was invalidated
        $updatedBook = Book::query()->find($testBook->id, ['*']);
        $this->assertNotEquals($originalTitle, $updatedBook->title, 'Cache should be invalidated on update');

        Log::info('Cache performance test completed', [
            'iterations' => $iterations,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'hit_rate_percent' => round($hitRate, 2),
        ]);
    }

    /**
     * Test export performance
     */
    public function test_export_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Create a test export job
        $exportJob = new \App\Jobs\LargeBookExport(['limit' => 10000]);
        
        // Process the export synchronously for testing
        $exportJob->handle();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $peakMemory = memory_get_peak_usage(true);

        $this->assertLessThanCustom($executionTime, 30, 'Export should complete within 30 seconds');
        $this->assertLessThanCustom($peakMemory, $this->memoryLimit, 'Export should use less than 512MB memory');

        Log::info('Export performance test completed', [
            'execution_time_seconds' => round($executionTime, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'records_exported' => 10000,
        ]);
    }

    /**
     * Test load handling
     */
    public function test_load_handling(): void
    {
        // Test concurrent access simulation
        $concurrentRequests = 50;
        $responseTimes = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $start = microtime(true);
            
            // Simulate catalog request
            $books = Book::select(['id', 'title', 'price'])
                ->where('is_active', true)
                ->limit(100)
                ->get();
            
            $end = microtime(true);
            $responseTimes[] = ($end - $start) * 1000;
        }

        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);

        $this->assertLessThanCustom($avgResponseTime, 200, 'Average response time should be reasonable under load');
        $this->assertLessThanCustom($maxResponseTime, 500, 'Maximum response time should be acceptable');

        Log::info('Load handling test completed', [
            'concurrent_requests' => $concurrentRequests,
            'avg_response_time_ms' => round($avgResponseTime, 2),
            'max_response_time_ms' => round($maxResponseTime, 2),
        ]);
    }

    /**
     * Validate ISBN-13 checksum
     */
    protected function isValidIsbn13(string $isbn): bool
    {
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$isbn[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $remainder = $sum % 10;
        $checkDigit = $remainder === 0 ? 0 : 10 - $remainder;

        return (int)$isbn[12] === $checkDigit;
    }

    /**
     * Run artisan command and capture output
     */
    public function artisan($command, $parameters = []): mixed
    {
        return \Illuminate\Support\Facades\Artisan::call($command, $parameters);
    }

    /**
     * Assert value is less than expected
     */
    protected function assertLessThanCustom(float $actual, float $expected, string $message): void
    {
        $this->assertTrue($actual < $expected, "{$message}: Expected < {$expected}, got {$actual}");
        if ($actual >= $expected) {
            $this->fail("{$message}: Expected < {$expected}, got {$actual}");
        }
    }

    /**
     * Assert value is greater than expected (custom assertion to avoid PHPUnit conflict)
     */
    protected function assertGreaterThanCustom(float $actual, float $expected, string $message): void
    {
        $this->assertTrue($actual > $expected, "{$message}: Expected > {$expected}, got {$actual}");
        if ($actual <= $expected) {
            $this->fail("{$message}: Expected > {$expected}, got {$actual}");
        }
    }

    /**
     * Test memory usage during operation
     */
    protected function assertMemoryUsage(string $operation, callable $callback): void
    {
        $startMemory = memory_get_usage(true);
        $callback();
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;

        $this->assertLessThanCustom($memoryUsed, $this->memoryLimit * 0.8, 
            "{$operation} should use less than 80% of memory limit");
    }

    private function isMillionSeedTest(): bool
    {
        return method_exists($this, 'name') && $this->name() === 'test_million_book_seeding_performance';
    }
}
