<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\LargeBookExport;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected int $testBookCount = 5000;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Category::factory()->create(['name' => 'Export']);

        // Create test books for export testing
        Book::factory()->count($this->testBookCount)->create();
    }

    /**
     * Test large export performance
     */
    public function test_large_export_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Create export job
        $exportJob = new LargeBookExport(['limit' => $this->testBookCount]);
        
        $exportJob->handle();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $peakMemory = memory_get_peak_usage(true);

        $this->assertLessThan(30, $executionTime, 'Large export should complete within 30 seconds');
        $this->assertLessThan(512 * 1024 * 1024, $peakMemory, 'Export should use less than 512MB memory');

        Log::info('Large export performance test completed', [
            'book_count' => $this->testBookCount,
            'execution_time_seconds' => round($executionTime, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
        ]);
    }

    /**
     * Test export memory efficiency
     */
    public function test_export_memory_efficiency(): void
    {
        $chunkSizes = [1000, 2000, 5000, 10000];
        
        foreach ($chunkSizes as $chunkSize) {
            $startMemory = memory_get_usage(true);
            
            // Simulate chunk processing
            $books = Book::take($chunkSize)->get(['id', 'title', 'author', 'price']);
            
            // Force garbage collection
            if ($chunkSize >= 5000) {
                gc_collect_cycles();
            }
            
            $endMemory = memory_get_usage(true);
            $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
            
            $this->assertLessThan(50, $memoryUsed, "Chunk size {$chunkSize} should use less than 50MB memory");
        }
    }

    /**
     * Test export queue processing
     */
    public function test_export_queue_processing(): void
    {
        // Create multiple export jobs
        $jobs = [];
        for ($i = 0; $i < 5; $i++) {
            $jobs[] = new LargeBookExport(['limit' => 1000, 'delay' => $i]);
        }

        Queue::fake();
        foreach ($jobs as $job) {
            LargeBookExport::dispatch($job->options);
        }

        Queue::assertPushed(LargeBookExport::class, count($jobs));
    }

    /**
     * Test export file generation
     */
    public function test_export_file_generation(): void
    {
        $exportJob = new LargeBookExport(['limit' => 1000]);
        
        // Process job synchronously for testing
        $exportJob->handle();
        
        // Verify file was created
        $files = Storage::files('exports');
        $this->assertGreaterThan(0, count($files), 'Export file should be created');
        
        // Verify file format
        foreach ($files as $file) {
            $this->assertTrue(str_ends_with($file, '.csv'), 'Export file should be CSV format');
            $this->assertGreaterThan(1000, strlen(Storage::disk('local')->get($file)), 'Export file should be larger than 1KB');
        }
    }

    /**
     * Test export error handling
     */
    public function test_export_error_handling(): void
    {
        // Test with invalid limit
        $this->expectException(\InvalidArgumentException::class);
        $invalidJob = new LargeBookExport(['limit' => -1]);
        $invalidJob->handle();
    }

    /**
     * Test export retry mechanism
     */
    public function test_export_retry_mechanism(): void
    {
        $retryAttempts = 0;
        $maxRetries = 3;
        
        while ($retryAttempts < $maxRetries) {
            try {
                $exportJob = new LargeBookExport(['limit' => 1000]);
                $exportJob->handle();
                
                $this->assertTrue(true, 'Export should succeed on attempt ' . ($retryAttempts + 1));
                break;
            } catch (\Exception $e) {
                $retryAttempts++;
                
                if ($retryAttempts < $maxRetries) {
                    continue;
                } else {
                    $this->fail('Export failed after ' . $maxRetries . ' attempts: ' . $e->getMessage());
                    return;
                }
            }
        }
    }

    /**
     * Test export progress tracking
     */
    public function test_export_progress_tracking(): void
    {
        $exportJob = new LargeBookExport(['limit' => 1000]);
        $exportJob->handle();

        $this->assertNotEmpty(Storage::disk('local')->files('exports'), 'Export progress should produce an output artifact');
    }

    /**
     * Test export format validation
     */
    public function test_export_format_validation(): void
    {
        // Test valid formats
        $validFormats = ['xlsx', 'csv', 'json'];
        foreach ($validFormats as $format) {
            $exportJob = new LargeBookExport(['format' => $format]);
            $this->assertTrue($exportJob->validateFormat(), "Format {$format} should be valid");
        }
        
        // Test invalid format
        $this->expectException(\InvalidArgumentException::class);
        $invalidJob = new LargeBookExport(['format' => 'invalid']);
        $invalidJob->validateFormat();
    }

    /**
     * Test export filter validation
     */
    public function test_export_filter_validation(): void
    {
        // Test valid filters
        $validFilters = [
            ['category_id' => 1],
            ['min_price' => 10.0],
            ['max_price' => 100.0],
            ['date_from' => '2023-01-01'],
            ['date_to' => '2023-12-31'],
        ];
        
        foreach ($validFilters as $filter) {
            $exportJob = new LargeBookExport($filter);
            $this->assertTrue($exportJob->validateFilters(), 'Valid filters should pass validation');
        }
        
        // Test invalid filters
        $invalidFilters = [
            ['category_id' => -1],
            ['min_price' => -10.0],
            ['max_price' => -100.0],
            ['date_from' => 'invalid-date'],
        ];
        
        foreach ($invalidFilters as $filter) {
            $this->expectException(\InvalidArgumentException::class);
            $exportJob = new LargeBookExport($filter);
            $exportJob->validateFilters();
        }
    }
}
