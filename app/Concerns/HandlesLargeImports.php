<?php

namespace App\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Services\MemoryMonitor;

trait HandlesLargeImports
{
    use Importable, WithChunkReading, WithHeadingRow;

    protected int $chunkSize = 1000;
    protected MemoryMonitor $memoryMonitor;
    protected array $importStats = [
        'total_rows' => 0,
        'processed_rows' => 0,
        'failed_rows' => 0,
        'errors' => [],
    ];

    /**
     * Set chunk size for memory-efficient processing
     */
    public function chunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Initialize import with memory monitoring
     */
    public function import(string $filePath, string $disk = null, string $format = null)
    {
        $this->memoryMonitor = new MemoryMonitor();
        $this->memoryMonitor->start();
        
        Log::info('Starting large import', [
            'file_path' => $filePath,
            'chunk_size' => $this->chunkSize,
            'initial_memory' => $this->memoryMonitor->getCurrentUsage(),
        ]);

        try {
            $result = parent::import($filePath, $disk, $format);
            
            $this->memoryMonitor->takeSnapshot('import_completed');
            
            Log::info('Import completed successfully', [
                'statistics' => $this->importStats,
                'memory_peak' => $this->memoryMonitor->getPeakUsage(),
                'execution_time' => $this->memoryMonitor->getExecutionTime(),
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->memoryMonitor->takeSnapshot('import_failed');
            
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'statistics' => $this->importStats,
                'memory_peak' => $this->memoryMonitor->getPeakUsage(),
                'execution_time' => $this->memoryMonitor->getExecutionTime(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Process chunk with memory monitoring
     */
    public function chunk(array $rows): void
    {
        $this->memoryMonitor->takeSnapshot('chunk_start');
        
        $chunkSize = count($rows);
        $initialMemory = $this->memoryMonitor->getCurrentUsage();
        
        try {
            // Process each row in the chunk
            foreach ($rows as $index => $row) {
                $this->processRow($row, $index);
                
                // Check memory usage every 100 rows
                if ($index % 100 === 0) {
                    $this->memoryMonitor->takeSnapshot("row_{$index}");
                    
                    // Log memory usage if approaching limit
                    if ($this->memoryMonitor->getMemoryPercentage() > 80) {
                        Log::warning('High memory usage during import', [
                            'row' => $index,
                            'memory_percentage' => $this->memoryMonitor->getMemoryPercentage(),
                            'chunk_size' => $chunkSize,
                        ]);
                    }
                }
            }
            
            $this->importStats['processed_rows'] += $chunkSize;
            
            $this->memoryMonitor->takeSnapshot('chunk_end');
            
            // Log chunk processing statistics
            Log::debug('Chunk processed', [
                'chunk_size' => $chunkSize,
                'memory_used' => $this->memoryMonitor->getCurrentUsage() - $initialMemory,
                'total_processed' => $this->importStats['processed_rows'],
            ]);
            
        } catch (\Exception $e) {
            $this->importStats['failed_rows'] += $chunkSize;
            $this->importStats['errors'][] = [
                'chunk' => floor($this->importStats['processed_rows'] / $this->chunkSize),
                'error' => $e->getMessage(),
                'row_count' => $chunkSize,
            ];
            
            Log::error('Chunk processing failed', [
                'error' => $e->getMessage(),
                'chunk_size' => $chunkSize,
                'processed_rows' => $this->importStats['processed_rows'],
            ]);
            
            throw $e;
        }
    }

    /**
     * Process individual row with validation and error handling
     */
    protected function processRow(array $row, int $index): void
    {
        try {
            // Validate required fields
            $this->validateRow($row);
            
            // Transform data if needed
            $transformedRow = $this->transformRow($row);
            
            // Insert into database using transaction for safety
            DB::transaction(function () use ($transformedRow) {
                $this->insertRow($transformedRow);
            });
            
        } catch (\Exception $e) {
            $this->importStats['failed_rows']++;
            $this->importStats['errors'][] = [
                'row' => $index,
                'data' => $row,
                'error' => $e->getMessage(),
            ];
            
            Log::warning('Row processing failed', [
                'row' => $index,
                'error' => $e->getMessage(),
                'data' => $row,
            ]);
        }
    }

    /**
     * Validate row data
     */
    protected function validateRow(array $row): void
    {
        // Check required fields
        $requiredFields = ['title', 'author', 'isbn', 'price'];
        
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        // Validate ISBN format
        if (!$this->isValidIsbn($row['isbn'])) {
            throw new \InvalidArgumentException("Invalid ISBN format: {$row['isbn']}");
        }
        
        // Validate price
        if (!is_numeric($row['price']) || $row['price'] < 0) {
            throw new \InvalidArgumentException("Invalid price: {$row['price']}");
        }
    }

    /**
     * Transform row data before insertion
     */
    protected function transformRow(array $row): array
    {
        return [
            'title' => trim($row['title'] ?? ''),
            'author' => trim($row['author'] ?? ''),
            'isbn' => $this->formatIsbn($row['isbn']),
            'price' => (float) $row['price'],
            'description' => trim($row['description'] ?? ''),
            'category_id' => $this->getCategoryId($row['category'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Insert row into database
     */
    protected function insertRow(array $row): void
    {
        // Use insert for better performance with large datasets
        DB::table('books')->insert($row);
    }

    /**
     * Get category ID from category name
     */
    protected function getCategoryId(?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }
        
        // Cache category lookups for better performance
        return cache()->remember(
            "category_id_{$categoryName}", 
            3600, // 1 hour cache
            function () use ($categoryName) {
                $category = DB::table('categories')
                    ->where('name', $categoryName)
                    ->first();
                
                return $category ? $category->id : null;
            }
        );
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
     * Format ISBN consistently
     */
    protected function formatIsbn(string $isbn): string
    {
        // Remove hyphens and spaces
        return str_replace(['-', ' '], '', $isbn);
    }

    /**
     * Get import statistics
     */
    public function getImportStats(): array
    {
        return array_merge($this->importStats, [
            'memory_statistics' => $this->memoryMonitor->getStatistics(),
            'chunk_size' => $this->chunkSize,
        ]);
    }

    /**
     * Get import failures
     */
    public function getFailures(): array
    {
        return $this->importStats['errors'];
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->importStats['total_rows'] === 0) {
            return 100.0;
        }
        
        return round(
            (($this->importStats['total_rows'] - $this->importStats['failed_rows']) / 
             $this->importStats['total_rows']) * 100, 
            2
        );
    }

    /**
     * Handle import completion
     */
    public function importCompleted(): void
    {
        Log::info('Import completed', [
            'statistics' => $this->getImportStats(),
            'memory_final' => $this->memoryMonitor->getCurrentUsage(),
        ]);
        
        // Clear cache if needed
        // cache()->flush(); // Uncomment if cache cleanup is needed
    }

    /**
     * Handle import failure
     */
    public function importFailed(\Exception $exception): void
    {
        Log::error('Import failed', [
            'error' => $exception->getMessage(),
            'statistics' => $this->getImportStats(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Set custom validation rules
     */
    public function setValidationRules(array $rules): self
    {
        $this->validationRules = $rules;
        return $this;
    }

    /**
     * Set custom transformations
     */
    public function setTransformations(callable $transformer): self
    {
        $this->customTransformer = $transformer;
        return $this;
    }

    /**
     * Enable/disable memory monitoring
     */
    public function enableMemoryMonitoring(bool $enabled = true): self
    {
        $this->memoryMonitoringEnabled = $enabled;
        return $this;
    }

    /**
     * Set memory limit in bytes
     */
    public function setMemoryLimit(int $limitBytes): self
    {
        $this->memoryMonitor = new MemoryMonitor($limitBytes);
        return $this;
    }

    /**
     * Get memory usage statistics
     */
    public function getMemoryStatistics(): array
    {
        return $this->memoryMonitor ? $this->memoryMonitor->getStatistics() : [];
    }

    /**
     * Check if memory limit was exceeded
     */
    public function exceededMemoryLimit(): bool
    {
        return $this->memoryMonitor ? $this->memoryMonitor->exceededLimit() : false;
    }

    /**
     * Log memory usage for debugging
     */
    protected function logMemoryUsage(string $context): void
    {
        if ($this->memoryMonitor) {
            $stats = $this->memoryMonitor->getStatistics();
            Log::debug($context, [
                'current_memory_mb' => round($stats['current_memory'] / 1024 / 1024, 2),
                'peak_memory_mb' => round($stats['peak_memory'] / 1024 / 1024, 2),
                'memory_percentage' => round($stats['memory_percentage'], 2),
                'execution_time_seconds' => round($stats['execution_time'], 2),
            ]);
        }
    }
}
