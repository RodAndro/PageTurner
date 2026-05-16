<?php

namespace App\Exports;

use App\Models\Book;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class BooksFromQueryExport implements ShouldQueue, FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use FromQuery;

    /**
     * Queue connection for export jobs
     */
    public string $queue = 'exports';

    /**
     * Export timeout in seconds
     */
    public int $timeout = 3600; // 1 hour

    /**
     * Number of times to attempt the job
     */
    public int $tries = 3;

    /**
     * Backoff time between attempts (in seconds)
     */
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Chunk size for processing
     */
    public int $chunkSize = 2000;

    /**
     * Optional filters for the query
     */
    protected array $filters = [];

    /**
     * Create a new export instance.
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the base query for the export.
     */
    public function query(): Builder
    {
        return Book::query()
            ->select([
                'isbn', 'title', 'author', 'price', 'stock_quantity',
                'publication_date', 'language', 'format', 'page_count',
                'rating', 'cover_image', 'category_id', 'created_at',
                'updated_at'
            ])
            ->where('is_active', true)
            ->when($this->filters['category_id'] ?? null, function ($query, $categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->when($this->filters['min_price'] ?? null, function ($query, $minPrice) {
                return $query->where('price', '>=', $minPrice);
            })
            ->when($this->filters['max_price'] ?? null, function ($query, $maxPrice) {
                return $query->where('price', '<=', $maxPrice);
            })
            ->when($this->filters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->where('publication_date', '>=', $dateFrom);
            })
            ->when($this->filters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->where('publication_date', '<=', $dateTo);
            })
            ->orderBy('publication_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get chunk size for processing.
     */
    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Set custom chunk size.
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Get the export headers.
     */
    public function headings(): array
    {
        return [
            'ISBN',
            'Title',
            'Author',
            'Price',
            'Stock Quantity',
            'Publication Date',
            'Language',
            'Format',
            'Page Count',
            'Rating',
            'Cover Image',
            'Category ID',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map data to export format.
     */
    public function map($book): array
    {
        return [
            $book->isbn,
            $book->title,
            $book->author,
            (float) $book->price,
            $book->stock_quantity,
            $book->publication_date,
            $book->language,
            $book->format,
            $book->page_count,
            $book->rating,
            $book->cover_image,
            $book->category_id,
            $book->created_at,
            $book->updated_at,
        ];
    }

    /**
     * Get the filename for the export.
     */
    public function fileName(): string
    {
        $date = now()->format('Y-m-d_H-i-s');
        $filterString = !empty($this->filters) ? '' : '_' . md5(serialize($this->filters));
        
        return "books_export_{$date}{$filterString}.xlsx";
    }

    /**
     * Get the file type for the export.
     */
    public function fileType(): string
    {
        return Excel::XLSX;
    }

    /**
     * Handle export start event.
     */
    public function startExport(): void
    {
        \Log::info('Books export started', [
            'filters' => $this->filters,
            'chunk_size' => $this->chunkSize,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Handle export completion event.
     */
    public function exportCompleted(): void
    {
        \Log::info('Books export completed', [
            'filters' => $this->filters,
            'final_memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Handle export failure event.
     */
    public function exportFailed(\Throwable $exception): void
    {
        \Log::error('Books export failed', [
            'filters' => $this->filters,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Process chunk with memory monitoring.
     */
    public function chunk(Collection $chunk, int $chunkIndex): void
    {
        $memoryBefore = memory_get_usage(true);
        
        // Process chunk
        foreach ($chunk as $book) {
            // Additional processing can be added here
            // For example: format data, calculate additional fields, etc.
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        
        // Log memory usage for large chunks
        if ($memoryUsed > 50) { // 50MB threshold
            \Log::warning('High memory usage in export chunk', [
                'chunk_index' => $chunkIndex,
                'chunk_size' => $chunk->count(),
                'memory_used_mb' => round($memoryUsed, 2),
                'memory_limit_mb' => 256, // Configurable limit
            ]);
            
            // Force garbage collection for large chunks
            if ($memoryUsed > 100) { // 100MB threshold
                gc_collect_cycles();
            }
        }
        
        \Log::debug('Export chunk processed', [
            'chunk_index' => $chunkIndex,
            'chunk_size' => $chunk->count(),
            'memory_used_mb' => round($memoryUsed, 2),
        ]);
    }

    /**
     * Get export statistics.
     */
    public function getExportStatistics(): array
    {
        return [
            'total_records' => $this->query()->count(),
            'estimated_chunks' => ceil($this->query()->count() / $this->chunkSize),
            'chunk_size' => $this->chunkSize,
            'filters' => $this->filters,
            'file_type' => $this->fileType(),
            'estimated_file_size_mb' => round($this->query()->count() * 0.002, 2), // ~2KB per record
        ];
    }

    /**
     * Prepare export with custom filters.
     */
    public function withFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Get memory usage statistics.
     */
    public function getMemoryStatistics(): array
    {
        return [
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit_mb' => ini_get('memory_limit'),
            'usage_percentage' => round((memory_get_usage(true) / 1024 / 1024) / (int)ini_get('memory_limit') * 100, 2),
        ];
    }

    /**
     * Validate export parameters.
     */
    public function validateExportParameters(): array
    {
        $errors = [];
        
        // Validate chunk size
        if ($this->chunkSize < 100 || $this->chunkSize > 10000) {
            $errors[] = 'Chunk size must be between 100 and 10,000 records';
        }
        
        // Validate date filters
        if (isset($this->filters['date_from']) && isset($this->filters['date_to'])) {
            $dateFrom = \Carbon\Carbon::parse($this->filters['date_from']);
            $dateTo = \Carbon\Carbon::parse($this->filters['date_to']);
            
            if ($dateFrom->gt($dateTo)) {
                $errors[] = 'Date from must be before or equal to date to';
            }
            
            if ($dateFrom->diffInDays($dateTo) > 365) {
                $errors[] = 'Date range cannot exceed 365 days';
            }
        }
        
        // Validate price filters
        if (isset($this->filters['min_price']) && isset($this->filters['max_price'])) {
            if ($this->filters['min_price'] < 0 || $this->filters['max_price'] < 0) {
                $errors[] = 'Price values must be positive';
            }
            
            if ($this->filters['min_price'] > $this->filters['max_price']) {
                $errors[] = 'Min price cannot be greater than max price';
            }
        }
        
        return $errors;
    }

    /**
     * Get export progress.
     */
    public function getExportProgress(): array
    {
        $total = $this->query()->count();
        $processed = 0; // This would be tracked during actual export
        
        return [
            'total_records' => $total,
            'processed_records' => $processed,
            'progress_percentage' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'estimated_remaining_time_minutes' => $processed > 0 ? round((($total - $processed) / $this->chunkSize) * 2, 2) : 0, // 2 minutes per chunk
            'current_chunk' => floor($processed / $this->chunkSize),
            'total_chunks' => ceil($total / $this->chunkSize),
        ];
    }
}
