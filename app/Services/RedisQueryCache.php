<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class RedisQueryCache
{
    protected string $prefix;
    protected int $defaultTtl;
    protected array $taggedKeys;
    protected bool $enabled;

    public function __construct()
    {
        $this->prefix = config('cache.prefix', 'pageturner');
        $this->defaultTtl = config('cache.redis.default_ttl', 3600); // 1 hour
        $this->taggedKeys = [];
        $this->enabled = config('cache.redis.enabled', true);
    }

    /**
     * Cache query results with intelligent tagging
     */
    public function remember(string $key, callable $callback, int $ttl = null, array $tags = [])
    {
        if (!$this->enabled) {
            return $callback();
        }

        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        // Check if cached result exists
        $cached = Redis::get($cacheKey);
        if ($cached !== null) {
            $this->recordCacheHit($cacheKey, $tags);
            return unserialize($cached);
        }

        // Execute callback and cache result
        try {
            $result = $callback();
            $serialized = serialize($result);
            
            // Store with tags
            Redis::setex($cacheKey, $ttl, $serialized);
            
            if (!empty($tags)) {
                $this->addTagsToKey($cacheKey, $tags);
            }

            $this->recordCacheMiss($cacheKey, $tags);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Cache callback failed', [
                'key' => $key,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Cache query results for database operations
     */
    public function rememberQuery(string $query, array $bindings, callable $callback, int $ttl = null, array $tags = [])
    {
        if (!$this->enabled) {
            return $callback();
        }

        $queryHash = md5($query . serialize($bindings));
        $cacheKey = "query:{$queryHash}";
        
        return $this->remember($cacheKey, $callback, $ttl, array_merge($tags, ['query', 'database']));
    }

    /**
     * Cache model results with automatic tagging
     */
    public function rememberModel(string $model, string $method, array $params, callable $callback, int $ttl = null)
    {
        if (!$this->enabled) {
            return $callback();
        }

        $paramHash = md5(serialize($params));
        $cacheKey = "model:{$model}:{$method}:{$paramHash}";
        
        return $this->remember($cacheKey, $callback, $ttl, ['model', $model, $method]);
    }

    /**
     * Cache search results with relevance scoring
     */
    public function rememberSearch(string $searchTerm, array $filters, callable $callback, int $ttl = null)
    {
        if (!$this->enabled) {
            return $callback();
        }

        $filterHash = md5(serialize($filters));
        $cacheKey = "search:" . md5($searchTerm) . ":{$filterHash}";
        
        return $this->remember($cacheKey, $callback, $ttl, ['search', 'query']);
    }

    /**
     * Cache catalog listing with pagination support
     */
    public function rememberCatalog(string $category, int $page, int $limit, callable $callback, int $ttl = null)
    {
        if (!$this->enabled) {
            return $callback();
        }

        $cacheKey = "catalog:{$category}:page:{$page}:limit:{$limit}";
        
        return $this->remember($cacheKey, $callback, $ttl, ['catalog', 'category:' . ($category ?: 'all')]);
    }

    /**
     * Cache bestseller data with automatic refresh
     */
    public function rememberBestsellers(string $period, callable $callback, int $ttl = null)
    {
        if (!$this->enabled) {
            return $callback();
        }

        $cacheKey = "bestsellers:{$period}";
        $ttl = $ttl ?? ($period === 'daily' ? 300 : 3600); // 5 min for daily, 1 hour for others
        
        return $this->remember($cacheKey, $callback, $ttl, ['bestsellers', $period]);
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTags(array $tags): void
    {
        if (!$this->enabled || empty($tags)) {
            return;
        }

        $pattern = $this->prefix . ':*:' . implode(':', $tags) . ':*';
        $keys = Redis::keys($pattern);

        if (!empty($keys)) {
            Redis::del($keys);
            
            Log::info('Cache invalidated by tags', [
                'tags' => $tags,
                'pattern' => $pattern,
                'keys_count' => count($keys),
            ]);
        }
    }

    /**
     * Invalidate cache by model
     */
    public function invalidateModel(string $model, string $id = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($id) {
            $pattern = $this->prefix . ':model:' . $model . ':*:' . $id . ':*';
        } else {
            $pattern = $this->prefix . ':model:' . $model . ':*';
        }

        $keys = Redis::keys($pattern);
        
        if (!empty($keys)) {
            Redis::del($keys);
            
            Log::info('Model cache invalidated', [
                'model' => $model,
                'id' => $id,
                'keys_count' => count($keys),
            ]);
        }
    }

    /**
     * Invalidate cache by category
     */
    public function invalidateCategory(string $category): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->invalidateTags(['category:' . $category]);
    }

    /**
     * Invalidate search cache
     */
    public function invalidateSearch(): void
    {
        if (!$this->enabled) {
            return;
        }

        $pattern = $this->prefix . ':search:*';
        $keys = Redis::keys($pattern);
        
        if (!empty($keys)) {
            Redis::del($keys);
            
            Log::info('Search cache invalidated', [
                'keys_count' => count($keys),
            ]);
        }
    }

    /**
     * Invalidate catalog cache
     */
    public function invalidateCatalog(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->invalidateTags(['catalog']);
    }

    /**
     * Get cache statistics
     */
    public function getStatistics(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        $info = Redis::info('memory');
        $keyspace = Redis::info('keyspace');
        
        return [
            'enabled' => true,
            'memory_used' => $info['used_memory_human'] ?? 'unknown',
            'memory_peak' => $info['maxmemory_human'] ?? 'unknown',
            'total_keys' => $keyspace['keys'] ?? 0,
            'expires_in' => $keyspace['expires'] ?? 0,
            'avg_ttl' => $keyspace['avg_ttl'] ?? 0,
        ];
    }

    /**
     * Warm up cache with common queries
     */
    public function warmUp(): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::info('Starting cache warmup');

        // Warm up popular categories
        $popularCategories = ['Fiction', 'Science Fiction', 'Mystery', 'Romance'];
        foreach ($popularCategories as $category) {
            $this->rememberCatalog($category, 1, 20, function () use ($category) {
                // Simulate catalog query
                return [
                    'category' => $category,
                    'books' => [],
                    'total' => 0,
                ];
            }, 300); // 5 minutes
        }

        // Warm up bestsellers
        $periods = ['daily', 'weekly', 'monthly'];
        foreach ($periods as $period) {
            $this->rememberBestsellers($period, function () use ($period) {
                // Simulate bestseller query
                return [
                    'period' => $period,
                    'books' => [],
                    'updated_at' => now(),
                ];
            }, $period === 'daily' ? 300 : 1800); // 5 min for daily, 30 min for others
        }

        Log::info('Cache warmup completed');
    }

    /**
     * Clean up expired cache entries
     */
    public function cleanup(): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $pattern = $this->prefix . ':*';
        $keys = Redis::keys($pattern);
        $expiredKeys = [];
        $cleanedCount = 0;

        foreach ($keys as $key) {
            $ttl = Redis::ttl($key);
            if ($ttl === -1 || $ttl === -2) { // -1: no expiration, -2: key doesn't exist
                continue;
            }
            
            if ($ttl <= 60) { // Keys expiring within 1 minute
                $expiredKeys[] = $key;
            }
        }

        if (!empty($expiredKeys)) {
            $cleanedCount = Redis::del($expiredKeys);
        }

        if ($cleanedCount > 0) {
            Log::info('Cache cleanup completed', [
                'expired_keys_count' => count($expiredKeys),
                'cleaned_keys_count' => $cleanedCount,
            ]);
        }

        return $cleanedCount;
    }

    /**
     * Get cache hit ratio
     */
    public function getHitRatio(): float
    {
        if (!$this->enabled) {
            return 0.0;
        }

        $stats = Redis::info('stats');
        $hits = $stats['keyspace_hits'] ?? 0;
        $misses = $stats['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Generate cache key with prefix
     */
    protected function getCacheKey(string $key): string
    {
        return $this->prefix . ':' . $key;
    }

    /**
     * Add tags to cache key
     */
    protected function addTagsToKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->prefix . ':tag:' . $tag . ':' . $key;
            Redis::setex($tagKey, $this->defaultTtl, '1');
        }
    }

    /**
     * Record cache hit
     */
    protected function recordCacheHit(string $key, array $tags): void
    {
        Log::debug('Cache hit', [
            'key' => $key,
            'tags' => $tags,
        ]);
    }

    /**
     * Record cache miss
     */
    protected function recordCacheMiss(string $key, array $tags): void
    {
        Log::debug('Cache miss', [
            'key' => $key,
            'tags' => $tags,
        ]);
    }

    /**
     * Get keys by tag
     */
    public function getKeysByTag(string $tag): array
    {
        if (!$this->enabled) {
            return [];
        }

        $pattern = $this->prefix . ':tag:' . $tag . ':*';
        $tagKeys = Redis::keys($pattern);
        
        return array_map(function ($tagKey) {
            // Extract original key from tag key
            return substr($tagKey, strlen($this->prefix . ':tag:' . $tag . ':'));
        }, $tagKeys);
    }

    /**
     * Get TTL for cache key
     */
    public function getTtl(string $key): int
    {
        if (!$this->enabled) {
            return -1;
        }

        $cacheKey = $this->getCacheKey($key);
        return Redis::ttl($cacheKey);
    }

    /**
     * Set TTL for cache key
     */
    public function setTtl(string $key, int $ttl): void
    {
        if (!$this->enabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($key);
        Redis::expire($cacheKey, $ttl);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $cacheKey = $this->getCacheKey($key);
        return Redis::exists($cacheKey);
    }

    /**
     * Delete cache key
     */
    public function forget(string $key): void
    {
        if (!$this->enabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($key);
        Redis::del($cacheKey);
        
        Log::debug('Cache key deleted', [
            'key' => $key,
        ]);
    }

    /**
     * Clear all cache
     */
    public function clear(): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $pattern = $this->prefix . ':*';
        $keys = Redis::keys($pattern);
        $clearedCount = 0;

        if (!empty($keys)) {
            $clearedCount = Redis::del($keys);
        }

        Log::info('Cache cleared', [
            'keys_count' => count($keys),
            'cleared_count' => $clearedCount,
        ]);

        return $clearedCount;
    }
}
