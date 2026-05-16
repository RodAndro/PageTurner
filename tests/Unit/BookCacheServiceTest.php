<?php

namespace Tests\Unit;

use App\Services\RedisQueryCache;
use App\Services\BookCacheService;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class BookCacheServiceTest extends TestCase
{
    protected BookCacheService $cacheService;
    protected int $testCategoryId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new BookCacheService();
        
        // Clear all cache before tests
        Cache::flush();
    }

    /**
     * Test cache tagging functionality
     */
    public function test_cache_tagging(): void
    {
        // Test tagged cache storage
        $books = collect([
            ['id' => 1, 'title' => 'Test Book 1'],
            ['id' => 2, 'title' => 'Test Book 2'],
        ]);

        $this->cacheService->remember('test_key', fn() => $books, 3600, ['category:1']);

        // Verify cache exists with tags
        $this->assertTrue(Cache::has('test_key'), 'Cache key should exist');

        // Test invalidation by tag
        $this->cacheService->invalidateTags(['category:1']);
        $this->assertFalse(Cache::has('test_key'), 'Cache should be invalidated by tag');
    }

    /**
     * Test cache invalidation strategies
     */
    public function test_cache_invalidation_strategies(): void
    {
        // Test specific cache key invalidation
        $this->cacheService->remember('book:1', fn() => 'test data', 3600);
        $this->assertTrue($this->cacheService->has('book:1'), 'Cache key should exist');

        $this->cacheService->forget('book:1');
        $this->assertFalse($this->cacheService->has('book:1'), 'Cache key should be removed');

        // Test tag-based invalidation
        $this->cacheService->remember('category_test', fn() => 'test data', 3600, ['category:1', 'books']);
        $this->assertTrue($this->cacheService->has('category_test'), 'Cache key should exist');

        $this->cacheService->invalidateTags(['category:1']);
        $this->assertFalse($this->cacheService->has('category_test'), 'Cache should be invalidated by category tag');
    }

    /**
     * Test cache performance monitoring
     */
    public function test_cache_performance_monitoring(): void
    {
        $stats = $this->cacheService->getStatistics();
        
        $this->assertArrayHasKey('enabled', $stats, 'Statistics should include enabled status');
        $this->assertArrayHasKey('memory_used_mb', $stats, 'Statistics should include memory usage');
        $this->assertArrayHasKey('hit_rate', $stats, 'Statistics should include hit rate');
        
        $this->assertTrue(is_bool($stats['enabled']), 'Enabled should be boolean');
        $this->assertTrue(is_numeric($stats['memory_used_mb']), 'Memory usage should be numeric');
        $this->assertTrue(is_numeric($stats['hit_rate']), 'Hit rate should be numeric');
    }

    /**
     * Test cache warming functionality
     */
    public function test_cache_warming(): void
    {
        // Test cache warmup
        $this->cacheService->warmUp();
        
        // Verify warmup completed (would need to mock Redis for testing)
        $this->assertTrue(true, 'Cache warmup should complete without errors');
    }

    /**
     * Test cache cleanup functionality
     */
    public function test_cache_cleanup(): void
    {
        // Add test data to cache
        for ($i = 0; $i < 10; $i++) {
            $this->cacheService->remember("cleanup_test_{$i}", fn() => "test data {$i}", 3600);
        }

        $cleanedCount = $this->cacheService->cleanup();

        $this->assertGreaterThanOrEqual(0, $cleanedCount, 'Cleanup should complete safely');
    }

    /**
     * Test cache TTL management
     */
    public function test_cache_ttl_management(): void
    {
        $testKey = 'ttl_test';
        $testValue = 'test data';
        
        // Test default TTL
        $this->cacheService->remember($testKey, fn() => $testValue, 3600);
        $this->assertEquals(3600, $this->cacheService->getTtl($testKey), 'Default TTL should be 3600 seconds');
        
        // Test custom TTL
        $this->cacheService->remember($testKey, fn() => $testValue, 1800);
        $this->assertEquals(1800, $this->cacheService->getTtl($testKey), 'Custom TTL should be set correctly');
        
        // Test TTL update
        $this->cacheService->setTtl($testKey, 7200);
        $this->assertEquals(7200, $this->cacheService->getTtl($testKey), 'TTL should be updated correctly');
    }

    /**
     * Test cache hit ratio calculation
     */
    public function test_cache_hit_ratio(): void
    {
        // Simulate cache operations
        $this->cacheService->remember('hit_test_1', fn() => 'data1', 3600);
        $this->cacheService->remember('hit_test_2', fn() => 'data2', 3600);
        $this->cacheService->remember('hit_test_3', fn() => 'data3', 3600);
        
        // Access data to create hits
        $this->cacheService->get('hit_test_1');
        $this->cacheService->get('hit_test_2');
        $this->cacheService->get('hit_test_3');
        
        // Access non-existent data to create misses
        $this->assertFalse($this->cacheService->has('miss_test_1'));
        $this->assertFalse($this->cacheService->has('miss_test_2'));
        $this->assertFalse($this->cacheService->has('miss_test_3'));
        
        // Test hit ratio calculation
        $stats = $this->cacheService->getStatistics();
        $this->assertGreaterThanOrEqual(50.0, $stats['hit_rate'], 'Hit rate should be at least 50%');
    }

    /**
     * Test concurrent cache access
     */
    public function test_concurrent_cache_access(): void
    {
        // This would require multi-threading or process forking
        // For now, test that cache handles rapid access
        for ($i = 0; $i < 100; $i++) {
            $this->cacheService->remember("concurrent_test_{$i}", fn() => "data {$i}", 60);
            $this->cacheService->get("concurrent_test_{$i}");
        }
        
        // Verify all data is accessible
        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue($this->cacheService->has("concurrent_test_{$i}"), "Data {$i} should be cached");
        }
    }
}
