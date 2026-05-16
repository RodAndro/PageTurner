<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BookCacheService
{
    private array $tags = [];
    private int $hits = 0;
    private int $misses = 0;

    public function remember(string $key, callable $callback, int $ttl = 3600, array $tags = []): mixed
    {
        if ($this->has($key)) {
            $this->hits++;
            Cache::put($this->ttlKey($key), $ttl, $ttl);
            return Cache::get($key);
        }

        $this->misses++;
        $value = $callback();
        Cache::put($key, $value, $ttl);
        Cache::put($this->ttlKey($key), $ttl, $ttl);

        foreach ($tags as $tag) {
            $this->tags[$tag] ??= [];
            $this->tags[$tag][$key] = true;
            Cache::put("cache_tag:{$tag}:{$key}", true, $ttl);
        }

        return $value;
    }

    public function get(string $key): mixed
    {
        if ($this->has($key)) {
            $this->hits++;
            return Cache::get($key);
        }

        $this->misses++;
        return null;
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
        Cache::forget($this->ttlKey($key));
    }

    public function invalidateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            foreach (array_keys($this->tags[$tag] ?? []) as $key) {
                $this->forget($key);
                Cache::forget("cache_tag:{$tag}:{$key}");
            }

            unset($this->tags[$tag]);
        }
    }

    public function getStatistics(): array
    {
        $total = $this->hits + $this->misses;

        return [
            'enabled' => true,
            'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'hit_rate' => $total > 0 ? round(($this->hits / $total) * 100, 2) : 0.0,
        ];
    }

    public function warmUp(): void
    {
        $this->remember('catalog:warm', fn () => [], 300, ['catalog']);
    }

    public function cleanup(): int
    {
        return 0;
    }

    public function getTtl(string $key): int
    {
        return (int) Cache::get($this->ttlKey($key), -1);
    }

    public function setTtl(string $key, int $ttl): void
    {
        if ($this->has($key)) {
            Cache::put($key, Cache::get($key), $ttl);
            Cache::put($this->ttlKey($key), $ttl, $ttl);
        }
    }

    public function getCacheKey(string $key): string
    {
        return $key;
    }

    private function ttlKey(string $key): string
    {
        return "{$key}:ttl";
    }
}
