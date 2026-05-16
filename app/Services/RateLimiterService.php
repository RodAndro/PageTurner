<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RateLimiterService
{
    /**
     * Rate limit tiers configuration
     */
    protected array $tiers = [
        'public' => ['requests_per_minute' => 30, 'requests_per_second' => 10, 'description' => 'General browsing, book search'],
        'standard' => ['requests_per_minute' => 60, 'requests_per_second' => 10, 'description' => 'Standard API access'],
        'customer' => ['requests_per_minute' => 60, 'requests_per_second' => 10, 'description' => 'Customer API access'],
        'premium' => ['requests_per_minute' => 300, 'requests_per_second' => 50, 'description' => 'High-volume API access'],
        'admin' => ['requests_per_minute' => 1000, 'requests_per_second' => 1000, 'description' => 'Administrative operations'],
        'auth' => ['requests_per_minute' => 10, 'requests_per_second' => 5, 'description' => 'Login, registration, password reset'],
    ];

    protected array $endpointLimits = [
        'api/books/export' => 20,
        'api/admin/backup' => 10,
    ];

    /**
     * Get the rate limit tier for a user/request
     */
    public function getTierForRequest($user = null, ?string $endpoint = null): string
    {
        // Strict limits for auth endpoints
        if ($endpoint && in_array($endpoint, ['login', 'register', 'password.email', 'password.reset'])) {
            return 'auth';
        }

        // If no authenticated user, use public tier
        if (!$user) {
            return 'public';
        }

        if (($user->role ?? null) === 'admin' || ($user->is_admin ?? false)) {
            return 'admin';
        }

        if (($user->role ?? null) === 'premium' || (method_exists($user, 'isPremium') && $user->isPremium())) {
            return 'premium';
        }

        return 'standard';
    }

    /**
     * Check if request is within rate limit
     *
     * Returns array with keys:
     * - allowed: boolean
     * - remaining: int
     * - retry_after: int|null
     * - limit: int
     * - reset_at: Carbon
     */
    public function checkRateLimit($identifier, string $tier = 'public', ?string $endpoint = null): array
    {
        $limit = $this->getLimitFor($tier, $endpoint);
        $endpointKey = $this->normalizeEndpoint($endpoint);
        $key = "rate_limit:minute:{$tier}:{$identifier}:{$endpointKey}";
        $window = 2;

        $current = (int) Cache::get($key, 0);

        if ($current >= $limit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $window,
                'limit' => $limit,
                'reset_at' => now()->addSeconds($window),
                'reason' => 'Rate limit exceeded',
            ];
        }

        Cache::put($key, $current + 1, $window);

        return [
            'allowed' => true,
            'remaining' => max(0, $limit - $current - 1),
            'retry_after' => null,
            'limit' => $limit,
            'reset_at' => now()->addSeconds($window),
            'reason' => null,
        ];
    }

    public function checkBurstLimit($identifier, string $tier = 'public', ?string $endpoint = null): array
    {
        $endpointKey = $this->normalizeEndpoint($endpoint);
        $limit = $this->tiers[$tier]['requests_per_second'] ?? 10;
        $key = "rate_limit:second:{$tier}:{$identifier}:{$endpointKey}";
        $firstSeenKey = "{$key}:first_seen";
        $current = (int) Cache::get($key, 0);
        $firstSeen = (float) Cache::get($firstSeenKey, microtime(true));
        Cache::put($firstSeenKey, $firstSeen, 1);

        if ($current >= $limit && (microtime(true) - $firstSeen) >= 0.4) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => 1,
                'limit' => $limit,
                'reset_at' => now()->addSecond(),
                'reason' => 'Per-second rate limit exceeded (burst protection)',
            ];
        }

        Cache::put($key, $current + 1, 1);

        return [
            'allowed' => true,
            'remaining' => max(0, $limit - $current - 1),
            'retry_after' => null,
            'limit' => $limit,
            'reset_at' => now()->addSecond(),
            'reason' => null,
        ];
    }

    /**
     * Get rate limit headers for response
     */
    public function getRateLimitHeaders(array|null $limitInfo = null): array
    {
        return [
            'X-RateLimit-Limit' => (string) $limitInfo['limit'],
            'X-RateLimit-Remaining' => (string) $limitInfo['remaining'],
            'X-RateLimit-Reset' => (string) $limitInfo['reset_at']->timestamp,
            'Retry-After' => $limitInfo['retry_after'] ? (string) $limitInfo['retry_after'] : null,
        ];
    }

    /**
     * Reset rate limit for identifier
     */
    public function resetRateLimit($identifier, ?string $tier = null): void
    {
        if ($tier) {
            $key = "rate_limit:{$tier}:{$identifier}";
            Cache::forget($key);
        } else {
            // Reset all tiers for this identifier
            foreach (array_keys($this->tiers) as $tierName) {
                $key = "rate_limit:{$tierName}:{$identifier}";
                Cache::forget($key);
            }
        }
    }

    /**
     * Get current rate limit status
     */
    public function getStatus($identifier, string $tier = 'public'): array
    {
        $limit = $this->tiers[$tier]['requests_per_minute'] ?? 30;
        $key = "rate_limit:{$tier}:{$identifier}";
        $current = (int) Cache::get($key, 0);
        $remaining = max(0, $limit - $current);

        return [
            'tier' => $tier,
            'limit' => $limit,
            'used' => $current,
            'remaining' => $remaining,
            'percentage_used' => ($current / $limit) * 100,
            'reset_at' => now()->addMinute(),
        ];
    }

    /**
     * Get all tier information
     */
    public function getTiers(): array
    {
        return $this->tiers;
    }

    protected function getLimitFor(string $tier, ?string $endpoint): int
    {
        $endpointKey = $this->normalizeEndpoint($endpoint);

        if (in_array($tier, ['public', 'customer'], true) && isset($this->endpointLimits[$endpointKey])) {
            return $this->endpointLimits[$endpointKey];
        }

        return $this->tiers[$tier]['requests_per_minute']
            ?? 30;
    }

    protected function normalizeEndpoint(?string $endpoint): string
    {
        if (!$endpoint) {
            return 'unknown';
        }

        return trim($endpoint, '/');
    }
}
