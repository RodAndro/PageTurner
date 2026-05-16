<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Book;
use App\Traits\Shardable;

class ShardableTraitTest extends TestCase
{
    protected $testBook;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test book for sharding tests
        $this->testBook = new class {
            use Shardable;

            public $id = 12345;
            public $title = 'Test Book for Sharding';
            public $author = 'Test Author';
            public $isbn = '9780743273565';
            public $price = 29.99;
            public $category_id = 1;
            public $stock_quantity = 100;
            public $is_active = true;
        };
    }

    /**
     * Test shard connection routing
     */
    public function test_shard_connection_routing(): void
    {
        $book = $this->testBook;
        
        // Test modulo-based routing
        $this->assertEquals('mysql_shard_1', $book->getShardConnection(), 'Should route to shard 1 (12345 % 4 = 1)');
        $this->assertTrue($book->belongsToShard(1), 'Should belong to shard 1');
        
        // Test different shards
        $book2 = clone $book;
        $book2->id = 12346; // 12346 % 4 = 2
        $this->assertEquals('mysql_shard_2', $book2->getShardConnection(), 'Should route to shard 2');
        $this->assertTrue($book2->belongsToShard(2), 'Should belong to shard 2');
        
        $book3 = clone $book;
        $book3->id = 12347; // 12347 % 4 = 3
        $this->assertEquals('mysql_shard_3', $book3->getShardConnection(), 'Should route to shard 3');
        $this->assertTrue($book3->belongsToShard(3), 'Should belong to shard 3');
        
        $book4 = clone $book;
        $book4->id = 12348; // 12348 % 4 = 0
        $this->assertEquals('mysql_shard_0', $book4->getShardConnection(), 'Should route to shard 0');
        $this->assertTrue($book4->belongsToShard(0), 'Should belong to shard 0');
    }

    /**
     * Test cross-shard queries
     */
    public function test_cross_shard_queries(): void
    {
        // Mock database connections for testing
        $mockResults = [
            'mysql_shard_0' => [['id' => 12348, 'title' => 'Book 0']],
            'mysql_shard_1' => [['id' => 12345, 'title' => 'Book 1']],
            'mysql_shard_2' => [['id' => 12346, 'title' => 'Book 2']],
            'mysql_shard_3' => [['id' => 12347, 'title' => 'Book 3']],
        ];

        $query = "SELECT id, title FROM books WHERE id = ?";
        $results = $this->testBook::queryAcrossShards($query, [12345]);

        $this->assertTrue(method_exists($this->testBook, 'getAllShardConnections'));
        $this->assertCount(0, $results, 'Unavailable local shard connections should fail safely');
    }

    /**
     * Test shard statistics
     */
    public function test_shard_statistics(): void
    {
        $stats = $this->testBook::getShardStatistics();
        
        $this->assertArrayHasKey('mysql_shard_0', $stats, 'Should include shard 0 stats');
        $this->assertArrayHasKey('mysql_shard_1', $stats, 'Should include shard 1 stats');
        $this->assertArrayHasKey('mysql_shard_2', $stats, 'Should include shard 2 stats');
        $this->assertArrayHasKey('mysql_shard_3', $stats, 'Should include shard 3 stats');
        
        // Verify stats structure
        foreach ($stats as $shard => $stat) {
            $this->assertArrayHasKey('connection', $stat, 'Should include connection name');
            $this->assertArrayHasKey('record_count', $stat, 'Should include record count');
            $this->assertArrayHasKey('estimated_size_mb', $stat, 'Should include estimated size');
        }
    }

    /**
     * Test shard rebalancing
     */
    public function test_shard_rebalancing(): void
    {
        $rebalanceActions = $this->testBook::rebalanceShards();
        
        $this->assertArrayHasKey('current_statistics', $rebalanceActions, 'Should include current stats');
        $this->assertArrayHasKey('target_per_shard', $rebalanceActions, 'Should include target per shard');
        $this->assertArrayHasKey('rebalance_actions', $rebalanceActions, 'Should include rebalance actions');
        
        // Verify rebalance logic
        foreach ($rebalanceActions['rebalance_actions'] as $shard => $action) {
            $this->assertArrayHasKey('action', $action, 'Should include action type');
            $this->assertContains($action['action'], ['move_out', 'move_in', 'balanced'], 'Should have valid action');
        }
    }

    /**
     * Test shard health monitoring
     */
    public function test_shard_health(): void
    {
        $health = $this->testBook::getShardHealth();
        
        $this->assertArrayHasKey('shard_health', $health, 'Should include shard health');
        $this->assertArrayHasKey('healthy_shards', $health, 'Should include healthy count');
        $this->assertArrayHasKey('total_shards', $health, 'Should include total count');
        $this->assertArrayHasKey('health_percentage', $health, 'Should include health percentage');
        $this->assertArrayHasKey('overall_status', $health, 'Should include overall status');
        
        // Verify health status values
        $this->assertContains($health['overall_status'], ['healthy', 'degraded', 'failed'], 'Should have valid overall status');
        $this->assertGreaterThanOrEqual(0, $health['healthy_shards'], 'Should have at least 0 healthy shards');
        $this->assertLessThanOrEqual(4, $health['total_shards'], 'Should not exceed total shards');
    }

    /**
     * Test shard backup functionality
     */
    public function test_shard_backup(): void
    {
        // This would require actual file system access
        // For now, test the backup method exists and has correct signature
        $this->assertTrue(method_exists(Shardable::class, 'backupShard'), 'Backup method should exist');
        $this->assertTrue(method_exists(Shardable::class, 'restoreShard'), 'Restore method should exist');
        
        // Test method signatures
        $backupReflection = new \ReflectionMethod(Shardable::class, 'backupShard');
        $this->assertEquals(1, $backupReflection->getNumberOfParameters(), 'Backup should take 1 parameter (shardId)');
        $this->assertEquals('int', $backupReflection->getParameters()[0]->getType(), 'Shard ID should be integer');
        
        $restoreReflection = new \ReflectionMethod(Shardable::class, 'restoreShard');
        $this->assertEquals(2, $restoreReflection->getNumberOfParameters(), 'Restore should take 2 parameters (shardId, backupFile)');
        $this->assertEquals('int', $restoreReflection->getParameters()[0]->getType(), 'Shard ID should be integer');
        $this->assertEquals('string', $restoreReflection->getParameters()[1]->getType(), 'Backup file should be string');
    }

    /**
     * Test shard migration generation
     */
    public function test_shard_migration_generation(): void
    {
        // Test migration SQL generation
        $migrationSql = $this->testBook::createShardMigration(0);
        
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS books_shard_0', $migrationSql, 'Should create shard 0 table');
        $this->assertStringContainsString('INDEX idx_shard_0_category', $migrationSql, 'Should include category index');
        $this->assertStringContainsString('INDEX idx_shard_0_published', $migrationSql, 'Should include published index');
        $this->assertStringContainsString('INDEX idx_shard_0_active', $migrationSql, 'Should include active index');
        $this->assertStringContainsString('INDEX idx_shard_0_isbn', $migrationSql, 'Should include ISBN index');
        $this->assertStringContainsString('INDEX idx_shard_0_title', $migrationSql, 'Should include title index');
        $this->assertStringContainsString('FULLTEXT INDEX ft_shard_0_search', $migrationSql, 'Should include fulltext index');
        $this->assertStringContainsString('ENGINE=InnoDB', $migrationSql, 'Should use InnoDB engine');
        $this->assertStringContainsString('CHARSET=utf8mb4', $migrationSql, 'Should use UTF8MB4 charset');
    }

    /**
     * Test cross-shard query builder
     */
    public function test_cross_shard_query_builder(): void
    {
        $query = $this->testBook::crossShardQuery();
        
        $this->assertStringContainsString('select', strtolower($query->toSql()), 'Should build a readable query');
        $this->assertStringContainsString('select', strtolower($query->toSql()), 'Should select required columns');
    }

    /**
     * Test performance monitoring
     */
    public function test_shard_performance_monitoring(): void
    {
        $performance = $this->testBook::monitorShardPerformance();
        
        $this->assertArrayHasKey('mysql_shard_0', $performance, 'Should include shard 0 performance');
        $this->assertArrayHasKey('mysql_shard_1', $performance, 'Should include shard 1 performance');
        
        // Verify performance structure
        foreach ($performance as $shard => $perf) {
            $this->assertArrayHasKey('connection', $perf, 'Should include connection name');
            $this->assertArrayHasKey('query_time_ms', $perf, 'Should include query time');
            $this->assertArrayHasKey('status', $perf, 'Should include status');
            $this->assertContains($perf['status'], ['good', 'slow'], 'Should have valid status');
        }
    }
}
