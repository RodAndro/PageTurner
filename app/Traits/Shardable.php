<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait Shardable
{
    /**
     * Get shard connection based on model ID
     * Uses modulo-based routing (id % 4) for 4 shards
     */
    public function getShardConnection(): string
    {
        $shardId = $this->id % 4; // 4 shards: 0, 1, 2, 3
        return "mysql_shard_{$shardId}";
    }

    /**
     * Get all shard connections for cross-shard queries
     */
    public function getAllShardConnections(): array
    {
        return [
            'mysql_shard_0',
            'mysql_shard_1', 
            'mysql_shard_2',
            'mysql_shard_3',
        ];
    }

    /**
     * Execute query across all shards
     */
    public static function queryAcrossShards(string $query, array $bindings = []): \Illuminate\Support\Collection
    {
        $results = collect();
        
        foreach ((new static)->getAllShardConnections() as $connection) {
            try {
                $shardResults = DB::connection($connection)->select($query, $bindings);
                $results = $results->merge($shardResults);
            } catch (\Exception $e) {
                // Log error but continue with other shards
                \Log::error("Shard query failed on {$connection}", [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Get shard table name
     */
    public function getShardTableName(): string
    {
        $shardId = $this->id % 4;
        return "books_shard_{$shardId}";
    }

    /**
     * Check if model belongs to specific shard
     */
    public function belongsToShard(int $shardId): bool
    {
        return $this->id % 4 === $shardId;
    }

    /**
     * Get shard statistics
     */
    public static function getShardStatistics(): array
    {
        $statistics = [];
        
        foreach ((new static)->getAllShardConnections() as $connection) {
            try {
                $count = DB::connection($connection)->table('books')->count();
                $statistics[$connection] = [
                    'connection' => $connection,
                    'record_count' => $count,
                    'estimated_size_mb' => round($count * 2 / 1024 / 1024, 2), // ~2KB per record
                ];
            } catch (\Exception $e) {
                $statistics[$connection] = [
                    'connection' => $connection,
                    'record_count' => 0,
                    'estimated_size_mb' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $statistics;
    }

    /**
     * Rebalance data across shards
     */
    public static function rebalanceShards(): array
    {
        $statistics = (new static)->getShardStatistics();
        $totalRecords = array_sum(array_column($statistics, 'record_count'));
        $targetPerShard = ceil($totalRecords / 4);
        
        $rebalanceActions = [];
        
        foreach ($statistics as $shard => $stats) {
            $currentCount = $stats['record_count'] ?? 0;
            
            if ($currentCount > $targetPerShard * 1.2) { // 20% above target
                $rebalanceActions[$shard] = [
                    'action' => 'move_out',
                    'records_to_move' => $currentCount - $targetPerShard,
                    'target_shard' => null, // Will be determined
                ];
            } elseif ($currentCount < $targetPerShard * 0.8) { // 20% below target
                $rebalanceActions[$shard] = [
                    'action' => 'move_in',
                    'records_to_move' => $targetPerShard - $currentCount,
                    'target_shard' => null, // Will be determined
                ];
            } else {
                $rebalanceActions[$shard] = [
                    'action' => 'balanced',
                    'records_to_move' => 0,
                    'target_shard' => null,
                ];
            }
        }
        
        return [
            'current_statistics' => $statistics,
            'target_per_shard' => $targetPerShard,
            'rebalance_actions' => $rebalanceActions,
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Create shard-specific migration
     */
    public static function createShardMigration(int $shardId): string
    {
        $tableName = "books_shard_{$shardId}";
        
        return "
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            author VARCHAR(200) NOT NULL,
            isbn VARCHAR(13) UNIQUE NOT NULL,
            price DECIMAL(8,2) NOT NULL,
            description TEXT,
            category_id BIGINT UNSIGNED,
            publisher VARCHAR(200),
            publication_date DATE,
            language VARCHAR(50),
            format VARCHAR(50),
            page_count INT UNSIGNED,
            rating DECIMAL(3,2),
            stock_quantity INT UNSIGNED DEFAULT 0,
            cover_image VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_shard_{$shardId}_category (category_id),
            INDEX idx_shard_{$shardId}_published (publication_date),
            INDEX idx_shard_{$shardId}_active (is_active),
            INDEX idx_shard_{$shardId}_isbn (isbn),
            INDEX idx_shard_{$shardId}_title (title),
            FULLTEXT INDEX ft_shard_{$shardId}_search (title, description)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }

    /**
     * Get cross-shard query builder
     */
    public static function crossShardQuery(): \Illuminate\Database\Query\Builder
    {
        $shardQueries = [];
        
        foreach ((new static)->getAllShardConnections() as $connection) {
            try {
                $shardQueries[] = DB::connection($connection)
                    ->table('books')
                    ->select(['id', 'title', 'author', 'price', 'stock_quantity']);
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($shardQueries)) {
            return DB::table('books')->select(['id', 'title', 'author', 'price', 'stock_quantity']);
        }
        
        // Union all shard queries
        $query = $shardQueries[0];
        
        for ($i = 1; $i < count($shardQueries); $i++) {
            $query->union($shardQueries[$i]);
        }
        
        return $query;
    }

    /**
     * Monitor shard performance
     */
    public static function monitorShardPerformance(): array
    {
        $performance = [];
        
        foreach ((new static)->getAllShardConnections() as $connection) {
            try {
                $startTime = microtime(true);
                
                // Test query performance
                $testQuery = DB::connection($connection)
                    ->table('books')
                    ->select('COUNT(*)')
                    ->where('is_active', true);
                
                $testQuery->get();
                
                $endTime = microtime(true);
                $queryTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
                
                $performance[$connection] = [
                    'connection' => $connection,
                    'query_time_ms' => round($queryTime, 2),
                    'status' => $queryTime < 100 ? 'good' : 'slow', // <100ms target
                ];
            } catch (\Exception $e) {
                $performance[$connection] = [
                    'connection' => $connection,
                    'error' => $e->getMessage(),
                    'query_time_ms' => 0,
                    'status' => 'slow',
                ];
            }
        }
        
        return $performance;
    }

    /**
     * Get shard health status
     */
    public static function getShardHealth(): array
    {
        $health = [];
        $healthyShards = 0;
        $totalShards = 4;
        
        foreach ((new static)->getAllShardConnections() as $connection) {
            try {
                // Test connection
                DB::connection($connection)->select('SELECT 1');
                $health[$connection] = [
                    'connection' => $connection,
                    'status' => 'healthy',
                    'last_check' => now()->toIso8601String(),
                ];
                $healthyShards++;
            } catch (\Exception $e) {
                $health[$connection] = [
                    'connection' => $connection,
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'last_check' => now()->toIso8601String(),
                ];
            }
        }
        
        return [
            'shard_health' => $health,
            'healthy_shards' => $healthyShards,
            'total_shards' => $totalShards,
            'health_percentage' => round(($healthyShards / $totalShards) * 100, 2),
            'overall_status' => $healthyShards === $totalShards ? 'healthy' : 'degraded',
        ];
    }

    /**
     * Backup shard data
     */
    public static function backupShard(int $shardId): bool
    {
        $connection = "mysql_shard_{$shardId}";
        
        try {
            $backupFile = storage_path("app/backups/shard_{$shardId}_backup_" . date('Y-m-d_H-i-s') . ".sql");
            
            // Use mysqldump for consistent backup
            $command = "mysqldump --single-transaction --quick --lock-tables=false " .
                       "--host=" . config("database.connections.{$connection}.host") . " " .
                       "--user=" . config("database.connections.{$connection}.username") . " " .
                       "--password=" . config("database.connections.{$connection}.password") . " " .
                       "--databases=" . config("database.connections.{$connection}.database") . " " .
                       "--tables=books > {$backupFile}";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                \Log::info("Shard {$shardId} backup completed", [
                    'backup_file' => $backupFile,
                    'file_size' => filesize($backupFile),
                ]);
                return true;
            } else {
                \Log::error("Shard {$shardId} backup failed", [
                    'command' => $command,
                    'return_code' => $returnCode,
                    'output' => $output,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error("Shard {$shardId} backup error", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Restore shard data
     */
    public static function restoreShard(int $shardId, string $backupFile): bool
    {
        $connection = "mysql_shard_{$shardId}";
        
        try {
            // Use mysql for consistent restore
            $command = "mysql --host=" . config("database.connections.{$connection}.host") . " " .
                       "--user=" . config("database.connections.{$connection}.username") . " " .
                       "--password=" . config("database.connections.{$connection}.password") . " " .
                       "--databases=" . config("database.connections.{$connection}.database") . " " .
                       "--execute=\"DROP TABLE IF EXISTS books; SOURCE {$backupFile};\"";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                \Log::info("Shard {$shardId} restore completed", [
                    'backup_file' => $backupFile,
                    'output' => $output,
                ]);
                return true;
            } else {
                \Log::error("Shard {$shardId} restore failed", [
                    'command' => $command,
                    'return_code' => $returnCode,
                    'output' => $output,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error("Shard {$shardId} restore error", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
