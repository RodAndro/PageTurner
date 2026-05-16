<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MemoryMonitor
{
    private float $startTime;
    private int $startMemory;
    private int $startPeakMemory;
    private array $snapshots = [];
    private int $memoryLimit;

    public function __construct(int $memoryLimitBytes = 256 * 1024 * 1024)
    {
        $this->memoryLimit = $memoryLimitBytes;
    }

    /**
     * Start monitoring memory usage
     */
    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->startPeakMemory = memory_get_peak_usage(true);
        $this->snapshots = [];
        
        $this->takeSnapshot('start');
    }

    /**
     * Take a snapshot of current memory usage
     */
    public function takeSnapshot(string $label): void
    {
        $this->snapshots[] = [
            'label' => $label,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_diff_from_start' => memory_get_usage(true) - $this->startMemory,
            'time_elapsed' => microtime(true) - $this->startTime,
        ];

        // Check if we're approaching memory limit
        $currentUsage = memory_get_usage(true);
        if ($currentUsage > $this->memoryLimit * 0.9) {
            Log::warning('Memory usage approaching limit', [
                'current_usage' => $currentUsage,
                'limit' => $this->memoryLimit,
                'percentage' => ($currentUsage / $this->memoryLimit) * 100,
                'snapshot' => $label,
            ]);
        }
    }

    /**
     * Get current memory usage
     */
    public function getCurrentUsage(): int
    {
        return memory_get_usage(true);
    }

    /**
     * Get peak memory usage
     */
    public function getPeakUsage(): int
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Get memory usage difference from start
     */
    public function getMemoryDiff(): int
    {
        return memory_get_usage(true) - $this->startMemory;
    }

    /**
     * Get total execution time
     */
    public function getExecutionTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Check if memory limit was exceeded
     */
    public function exceededLimit(): bool
    {
        return memory_get_peak_usage(true) > $this->memoryLimit;
    }

    /**
     * Get memory usage percentage
     */
    public function getMemoryPercentage(): float
    {
        return (memory_get_usage(true) / $this->memoryLimit) * 100;
    }

    /**
     * Get all snapshots
     */
    public function getSnapshots(): array
    {
        return $this->snapshots;
    }

    /**
     * Get memory usage statistics
     */
    public function getStatistics(): array
    {
        return [
            'start_memory' => $this->startMemory,
            'current_memory' => $this->getCurrentUsage(),
            'peak_memory' => $this->getPeakUsage(),
            'memory_diff' => $this->getMemoryDiff(),
            'memory_limit' => $this->memoryLimit,
            'memory_percentage' => $this->getMemoryPercentage(),
            'execution_time' => $this->getExecutionTime(),
            'exceeded_limit' => $this->exceededLimit(),
            'snapshots' => $this->snapshots,
        ];
    }

    /**
     * Log memory statistics
     */
    public function logStatistics(string $context = 'Memory Monitor'): void
    {
        $stats = $this->getStatistics();
        
        Log::info($context, [
            'memory_used_mb' => round($stats['memory_diff'] / 1024 / 1024, 2),
            'peak_memory_mb' => round($stats['peak_memory'] / 1024 / 1024, 2),
            'memory_percentage' => round($stats['memory_percentage'], 2),
            'execution_time_seconds' => round($stats['execution_time'], 2),
            'exceeded_limit' => $stats['exceeded_limit'],
        ]);
    }

    /**
     * Assert memory usage is within limits
     */
    public function assertMemoryLimit(string $message = ''): void
    {
        if ($this->exceededLimit()) {
            $stats = $this->getStatistics();
            throw new \RuntimeException(
                ($message ?: 'Memory limit exceeded') . '. ' .
                "Used: " . round($stats['memory_diff'] / 1024 / 1024, 2) . "MB, " .
                "Peak: " . round($stats['peak_memory'] / 1024 / 1024, 2) . "MB, " .
                "Limit: " . round($this->memoryLimit / 1024 / 1024, 2) . "MB"
            );
        }
    }

    /**
     * Monitor a callable function and return statistics
     */
    public function monitor(callable $callback, string $label = 'operation'): array
    {
        $this->start();
        
        try {
            $result = $callback();
            $this->takeSnapshot($label . '_completed');
            
            return [
                'result' => $result,
                'statistics' => $this->getStatistics(),
            ];
        } catch (\Exception $e) {
            $this->takeSnapshot($label . '_failed');
            
            throw $e;
        }
    }

    /**
     * Format bytes to human readable format
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
