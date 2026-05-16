<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use Illuminate\Support\Facades\Cache;

class WarmCategoryCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 3600; // 1 hour timeout
    public int $tries = 3;
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $categoryId,
        public int $limit = 1000
    ) {
        $this->categoryId = $categoryId;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        Log::info('Starting category cache warmup', [
            'category_id' => $this->categoryId,
            'limit' => $this->limit,
        ]);

        try {
            // Get top books for this category
            $books = Book::select([
                    'id', 'title', 'author', 'isbn', 'price',
                    'description', 'publication_date', 'language', 'format',
                    'page_count', 'rating', 'stock_quantity', 'cover_image',
                    'category_id', 'created_at', 'updated_at'
                ])
                ->where('category_id', $this->categoryId)
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($this->limit)
                ->get();

            if ($books->isEmpty()) {
                Log::info('No books found for category', [
                    'category_id' => $this->categoryId,
                ]);
                return;
            }

            // Warm up cache with tagged entries
            $cacheKey = "category:{$this->categoryId}:popular";
            $tag = ["category:{$this->categoryId}"];
            
            Cache::tags($tag)->put($cacheKey, $books->toArray(), 7200); // 2 hours

            // Also warm up individual book caches for top 100
            $topBooks = $books->take(100);
            
            foreach ($topBooks as $book) {
                $bookCacheKey = "book:{$book->id}";
                Cache::tags(["book:{$book->id}", "category:{$this->categoryId}"])
                    ->put($bookCacheKey, $book->toArray(), 3600); // 1 hour
            }

            $executionTime = microtime(true) - $startTime;
            
            Log::info('Category cache warmup completed', [
                'category_id' => $this->categoryId,
                'books_count' => $books->count(),
                'top_books_cached' => $topBooks->count(),
                'execution_time_seconds' => round($executionTime, 2),
                'cache_ttl_hours' => 2,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('Category cache warmup failed', [
                'category_id' => $this->categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Category cache warmup job failed', [
            'category_id' => $this->categoryId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * The job was processed successfully.
     */
    public function success(): void
    {
        Log::info('Category cache warmup job completed successfully', [
            'category_id' => $this->categoryId,
        ]);
    }

    /**
     * Get the tags used by the job.
     */
    public function tags(): array
    {
        return ["category:{$this->categoryId}"];
    }

    /**
     * Get the display name for the job.
     */
    public function displayName(): string
    {
        return "Warm Category Cache (ID: {$this->categoryId})";
    }

    /**
     * Get the unique identifier for the job.
     */
    public function uniqueId(): string
    {
        return 'warm_category_cache_' . $this->categoryId . '_' . time();
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function retryAfter(): int
    {
        return 60;
    }
}
