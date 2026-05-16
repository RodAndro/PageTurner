<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamingExportService
{
    protected int $chunkSize = 1000;
    protected int $memoryLimit = 256 * 1024 * 1024; // 256MB
    protected array $columns = [];
    protected string $format = 'csv';

    /**
     * Export books with streaming
     * Target: <30s for 10K records
     */
    public function exportBooks(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $this->format = $format;
        $this->columns = $this->getBookColumns();
        
        Log::info('Starting streaming book export', [
            'filters' => $filters,
            'format' => $format,
            'chunk_size' => $this->chunkSize,
        ]);

        $startTime = microtime(true);

        return response()->streamDownload(function () use ($filters, $startTime) {
            $this->writeHeader();
            
            // Get total count for progress tracking
            $totalQuery = $this->buildBookQuery($filters);
            $total = $totalQuery->count();
            
            Log::info('Book export started', [
                'total_records' => $total,
                'estimated_chunks' => ceil($total / $this->chunkSize),
            ]);

            // Stream data in chunks
            $offset = 0;
            $processedCount = 0;
            
            do {
                $chunk = $this->getBookChunk($filters, $offset, $this->chunkSize);
                
                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $record) {
                    $this->writeRecord($record);
                    $processedCount++;
                    
                    // Log progress every 1000 records
                    if ($processedCount % 1000 === 0) {
                        $progress = round(($processedCount / $total) * 100, 2);
                        $elapsedTime = microtime(true) - $startTime;
                        $estimatedTotal = ($elapsedTime / $processedCount) * $total;
                        $remainingTime = $estimatedTotal - $elapsedTime;
                        
                        Log::debug('Export progress', [
                            'processed' => $processedCount,
                            'total' => $total,
                            'progress_percent' => $progress,
                            'elapsed_seconds' => round($elapsedTime, 2),
                            'estimated_remaining_seconds' => round($remainingTime, 2),
                        ]);
                    }
                }
                
                $offset += $this->chunkSize;
                
                // Force garbage collection to manage memory
                if ($offset % ($this->chunkSize * 5) === 0) {
                    gc_collect_cycles();
                }
                
            } while ($chunk->isNotEmpty());
            
            $totalTime = microtime(true) - $startTime;
            
            Log::info('Book export completed', [
                'total_records' => $processedCount,
                'total_time_seconds' => round($totalTime, 2),
                'records_per_second' => round($processedCount / $totalTime, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
            
        }, $this->generateFilename('books', $format), $this->getContentType($format));
    }

    /**
     * Export orders with streaming
     */
    public function exportOrders(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $this->format = $format;
        $this->columns = $this->getOrderColumns();
        
        Log::info('Starting streaming order export', [
            'filters' => $filters,
            'format' => $format,
            'chunk_size' => $this->chunkSize,
        ]);

        $startTime = microtime(true);

        return response()->streamDownload(function () use ($filters, $startTime) {
            $this->writeHeader();
            
            $totalQuery = $this->buildOrderQuery($filters);
            $total = $totalQuery->count();
            
            Log::info('Order export started', [
                'total_records' => $total,
            ]);

            $offset = 0;
            $processedCount = 0;
            
            do {
                $chunk = $this->getOrderChunk($filters, $offset, $this->chunkSize);
                
                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $record) {
                    $this->writeRecord($record);
                    $processedCount++;
                }
                
                $offset += $this->chunkSize;
                
                if ($offset % ($this->chunkSize * 3) === 0) {
                    gc_collect_cycles();
                }
                
            } while ($chunk->isNotEmpty());
            
            $totalTime = microtime(true) - $startTime;
            
            Log::info('Order export completed', [
                'total_records' => $processedCount,
                'total_time_seconds' => round($totalTime, 2),
                'records_per_second' => round($processedCount / $totalTime, 2),
            ]);
            
        }, $this->generateFilename('orders', $format), $this->getContentType($format));
    }

    /**
     * Export user data with streaming
     */
    public function exportUsers(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $this->format = $format;
        $this->columns = $this->getUserColumns();
        
        Log::info('Starting streaming user export', [
            'filters' => $filters,
            'format' => $format,
        ]);

        $startTime = microtime(true);

        return response()->streamDownload(function () use ($filters, $startTime) {
            $this->writeHeader();
            
            $totalQuery = $this->buildUserQuery($filters);
            $total = $totalQuery->count();
            
            $offset = 0;
            $processedCount = 0;
            
            do {
                $chunk = $this->getUserChunk($filters, $offset, $this->chunkSize);
                
                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $record) {
                    $this->writeRecord($record);
                    $processedCount++;
                }
                
                $offset += $this->chunkSize;
                
                if ($offset % ($this->chunkSize * 2) === 0) {
                    gc_collect_cycles();
                }
                
            } while ($chunk->isNotEmpty());
            
            $totalTime = microtime(true) - $startTime;
            
            Log::info('User export completed', [
                'total_records' => $processedCount,
                'total_time_seconds' => round($totalTime, 2),
            ]);
            
        }, $this->generateFilename('users', $format), $this->getContentType($format));
    }

    /**
     * Build book query with filters
     */
    protected function buildBookQuery(array $filters)
    {
        $query = Book::select($this->columns);
        
        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('name', $filters['category']);
            });
        }
        
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query;
    }

    /**
     * Build order query with filters
     */
    protected function buildOrderQuery(array $filters)
    {
        $query = Order::select($this->columns)
            ->with(['user', 'orderItems.book']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query;
    }

    /**
     * Build user query with filters
     */
    protected function buildUserQuery(array $filters)
    {
        $query = User::select($this->columns);
        
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        
        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }
        
        return $query;
    }

    /**
     * Get book chunk
     */
    protected function getBookChunk(array $filters, int $offset, int $limit)
    {
        return $this->buildBookQuery($filters)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Get order chunk
     */
    protected function getOrderChunk(array $filters, int $offset, int $limit)
    {
        return $this->buildOrderQuery($filters)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Get user chunk
     */
    protected function getUserChunk(array $filters, int $offset, int $limit)
    {
        return $this->buildUserQuery($filters)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Write CSV header
     */
    protected function writeHeader(): void
    {
        if ($this->format === 'csv') {
            echo implode(',', array_map([$this, 'escapeCsvField'], $this->columns)) . "\n";
        } elseif ($this->format === 'json') {
            echo "[\n";
        }
    }

    /**
     * Write record to output
     */
    protected function writeRecord($record): void
    {
        if ($this->format === 'csv') {
            $this->writeCsvRecord($record);
        } elseif ($this->format === 'json') {
            $this->writeJsonRecord($record);
        }
    }

    /**
     * Write CSV record
     */
    protected function writeCsvRecord($record): void
    {
        $values = [];
        foreach ($this->columns as $column) {
            $value = $this->getRecordValue($record, $column);
            $values[] = $this->escapeCsvField($value);
        }
        echo implode(',', $values) . "\n";
    }

    /**
     * Write JSON record
     */
    protected function writeJsonRecord($record): void
    {
        $data = [];
        foreach ($this->columns as $column) {
            $data[$column] = $this->getRecordValue($record, $column);
        }
        echo json_encode($data) . ",\n";
    }

    /**
     * Get record value by column
     */
    protected function getRecordValue($record, string $column)
    {
        if (str_contains($column, '.')) {
            // Handle nested relationships
            $parts = explode('.', $column);
            $value = $record;
            
            foreach ($parts as $part) {
                if (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } elseif (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    $value = null;
                    break;
                }
            }
            
            return $value;
        }
        
        return is_object($record) ? ($record->$column ?? null) : ($record[$column] ?? null);
    }

    /**
     * Escape CSV field
     */
    protected function escapeCsvField($value): string
    {
        if ($value === null) {
            return '';
        }
        
        $value = (string) $value;
        
        // Escape quotes and commas
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        
        return $value;
    }

    /**
     * Get book columns
     */
    protected function getBookColumns(): array
    {
        return [
            'id',
            'title',
            'author',
            'isbn',
            'price',
            'description',
            'category.name',
            'publisher',
            'publication_date',
            'language',
            'format',
            'page_count',
            'rating',
            'stock_quantity',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get order columns
     */
    protected function getOrderColumns(): array
    {
        return [
            'id',
            'user.email',
            'user.first_name',
            'user.last_name',
            'status',
            'total_amount',
            'currency',
            'created_at',
            'shipped_at',
            'delivered_at',
            'order_items_count',
            'total_items',
        ];
    }

    /**
     * Get user columns
     */
    protected function getUserColumns(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'role',
            'phone',
            'created_at',
            'last_login_at',
            'orders_count',
            'total_spent',
        ];
    }

    /**
     * Generate filename
     */
    protected function generateFilename(string $type, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$type}_export_{$timestamp}.{$format}";
    }

    /**
     * Get content type
     */
    protected function getContentType(string $format): string
    {
        $contentTypes = [
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        
        return $contentTypes[$format] ?? 'text/csv';
    }

    /**
     * Set chunk size
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Set memory limit
     */
    public function setMemoryLimit(int $limit): self
    {
        $this->memoryLimit = $limit;
        return $this;
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        return [
            'chunk_size' => $this->chunkSize,
            'memory_limit' => $this->memoryLimit,
            'current_memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Optimize for large exports
     */
    protected function optimizeForLargeExport(): void
    {
        // Disable query logging for performance
        DB::disableQueryLog();
        
        // Increase memory limit if needed
        ini_set('memory_limit', $this->memoryLimit);
        
        // Set longer execution time
        set_time_limit(300); // 5 minutes
        
        Log::info('Export optimization applied', [
            'memory_limit' => $this->memoryLimit,
            'time_limit' => 300,
        ]);
    }

    /**
     * Check memory usage and adjust if needed
     */
    protected function checkMemoryUsage(): void
    {
        $currentUsage = memory_get_usage(true);
        $usagePercentage = ($currentUsage / $this->memoryLimit) * 100;
        
        if ($usagePercentage > 80) {
            Log::warning('High memory usage during export', [
                'current_usage_mb' => round($currentUsage / 1024 / 1024, 2),
                'limit_mb' => round($this->memoryLimit / 1024 / 1024, 2),
                'usage_percentage' => round($usagePercentage, 2),
            ]);
            
            // Force garbage collection
            gc_collect_cycles();
            
            // Reduce chunk size if memory is critically high
            if ($usagePercentage > 95) {
                $this->chunkSize = max(100, intval($this->chunkSize * 0.5));
                Log::info('Reduced chunk size due to memory pressure', [
                    'new_chunk_size' => $this->chunkSize,
                    'memory_usage_percentage' => round($usagePercentage, 2),
                ]);
            }
        }
    }
}
