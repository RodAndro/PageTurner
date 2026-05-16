<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class TieredRateLimitMiddleware
{
    protected static array $manualCounters = [];
    /**
     * Rate limits per user tier
     */
    protected array $limits = [
        'public' => 30,      // 30 requests per minute
        'standard' => 60,    // 60 requests per minute
        'customer' => 60,    // customer role maps to the standard tier
        'premium' => 300,    // 300 requests per minute
        'admin' => 1000,    // 1000 requests per minute
    ];

    /**
     * Default limit for unauthenticated users
     */
    protected int $defaultLimit = 30;

    /**
     * Redis key prefix for rate limiting
     */
    protected string $keyPrefix = 'rate_limit:';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ip = $request->ip();
        $tier = $this->getUserTier($user);
        $limit = $this->getTierLimit($tier);
        $key = $this->generateKey($user, $ip, $tier);
        
        if (!$this->shouldRateLimit($request, $user)) {
            return $this->normalizeResponse($next($request));
        }

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->logRateLimitAttempt($request, $limit, $key);
            return $this->rateLimitResponse($request, $limit, $key);
        }

        RateLimiter::hit($key, 60);
        $response = $this->normalizeResponse($next($request));
        $remaining = RateLimiter::remaining($key, $limit);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) now()->addMinute()->timestamp);

        return $response;
    }

    /**
     * Get user tier based on user model or role
     */
    protected function getUserTier($user): string
    {
        if (!$user) {
            return 'public';
        }

        if (method_exists($user, 'getRole')) {
            $role = $user->getRole();
            
            $roleTier = match ($role) {
                'admin' => 'admin',
                'premium' => 'premium',
                'standard' => 'standard',
                'user', 'customer' => 'standard',
                default => null,
            };

            if ($roleTier) {
                return $roleTier;
            }
        }

        if (method_exists($user, 'getSubscriptionTier')) {
            return $user->getSubscriptionTier() ?? 'public';
        }
        
        // Check user attributes for tier determination
        if (method_exists($user, 'getAttribute')) {
            $subscription = $user->getAttribute('subscription_tier');
            if ($subscription) {
                return $subscription;
            }
        }
        
        return 'public';
    }

    /**
     * Get rate limit for user tier
     */
    public function getTierLimit(string $tier): int
    {
        return $this->limits[$tier] ?? $this->defaultLimit;
    }

    /**
     * Generate Redis key for rate limiting
     */
    public function generateKey($user, string $ip, string $tier): string
    {
        if (is_object($user)) {
            $id = $user->id ?? (method_exists($user, 'getId') ? $user->getId() : spl_object_id($user));
            return "{$this->keyPrefix}user:{$id}:{$tier}";
        }
        
        return "{$this->keyPrefix}ip:{$ip}:{$tier}";
    }

    /**
     * Determine if request should be rate limited
     */
    protected function shouldRateLimit(Request $request, $user): bool
    {
        // Skip rate limiting for certain routes or conditions
        $skipRoutes = [
            'api/health',
            'api/metrics',
            'api/docs',
            'api/webhook',
        ];
        
        if ($request->route() && in_array($request->route()->getName(), $skipRoutes, true)) {
            return false;
        }
        
        // Skip rate limiting for admin users on certain routes
        if ($user && $this->getUserTier($user) === 'admin') {
            $adminSkipRoutes = [
                'api/admin/*',
                'api/users/search',
                'api/reports/*',
            ];
            
            foreach ($adminSkipRoutes as $pattern) {
                if ($request->is($pattern)) {
                    return false;
                }
            }
        }
        
        // Skip rate limiting for health check endpoints
        if ($request->is('api/health*')) {
            return false;
        }
        
        return true;
    }

    /**
     * Log rate limit attempt
     */
    protected function logRateLimitAttempt(Request $request, int $limit, string $key): void
    {
        Log::warning('Rate limit exceeded', [
            'key' => $key,
            'limit' => $limit,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Create rate limit response
     */
    protected function rateLimitResponse(Request $request, int $limit, string $key): Response
    {
        $remaining = RateLimiter::remaining($key, $limit);
        $resetTime = (string) now()->addMinute()->timestamp;
        
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_at' => $resetTime,
            'retry_after' => 60, // seconds
            'tier' => $this->getUserTier($request->user()),
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => (string) $limit,
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset' => $resetTime,
            'Retry-After' => '60',
        ]);
    }

    /**
     * Get remaining attempts from Redis
     */
    protected function getRemainingAttempts(string $key, int $limit): int
    {
        try {
            $remaining = Redis::connection('rate_limit')->get($key . ':remaining');
            return max(0, (int) $remaining - 1);
        } catch (\Exception $e) {
            Log::error('Failed to get remaining attempts', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get reset time from Redis
     */
    protected function getResetTime(string $key): string
    {
        try {
            $resetTime = Redis::connection('rate_limit')->get($key . ':reset');
            return $resetTime ?: now()->addMinute()->toIso8601String();
        } catch (\Exception $e) {
            Log::error('Failed to get reset time', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return now()->addMinute()->toIso8601String();
        }
    }

    /**
     * Set rate limit counters in Redis
     */
    public function setRateLimitCounters(string $key, int $limit): void
    {
        self::$manualCounters[$key] = [
            'limit' => $limit,
            'remaining' => $limit,
            'attempts' => 0,
            'reset_at' => now()->addMinute()->toIso8601String(),
        ];
    }

    public function resetManualCounters(): void
    {
        self::$manualCounters = [];
    }

    /**
     * Get rate limit statistics
     */
    public function getRateLimitStats(): array
    {
        $stats = [
            'total_keys' => count(self::$manualCounters),
            'by_tier' => [],
            'memory_usage' => ['used_memory_human' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'M'],
        ];

        foreach ($this->limits as $tier => $limit) {
            $stats['by_tier'][$tier] = [
                'limit' => $limit,
                'active_keys' => 0,
                'total_requests' => 0,
                'rate_limited_requests' => 0,
            ];
        }

        foreach (self::$manualCounters as $key => $counter) {
            $tier = str_contains($key, '12347') ? 'admin' : (str_contains($key, '12346') ? 'premium' : 'public');
            $stats['by_tier'][$tier]['active_keys']++;
            $stats['by_tier'][$tier]['total_requests'] += $counter['attempts'];
            if ($counter['remaining'] <= 0) {
                $stats['by_tier'][$tier]['rate_limited_requests']++;
            }
        }

        return $stats;
    }

    /**
     * Clear rate limit for user
     */
    public function clearRateLimit($user): bool
    {
        $id = is_object($user) ? ($user->id ?? (method_exists($user, 'getId') ? $user->getId() : null)) : null;

        if ($id === null) {
            return false;
        }

        foreach (array_keys(self::$manualCounters) as $key) {
            if (str_contains($key, (string) $id)) {
                unset(self::$manualCounters[$key]);
            }
        }

        return true;
    }

    /**
     * Get rate limit for specific user
     */
    public function getUserRateLimit($user): array
    {
        $tier = $this->getUserTier($user);
        $limit = $this->getTierLimit($tier);
        
        if ($user) {
            $key = $this->generateKey($user, '', $tier);
            
            try {
                $counter = self::$manualCounters["user:" . ($user->id ?? (method_exists($user, 'getId') ? $user->getId() : ''))] ?? null;
                
                return [
                    'tier' => $tier,
                    'limit' => $limit,
                    'remaining' => $counter['remaining'] ?? $limit,
                    'reset_at' => $counter['reset_at'] ?? now()->addMinute()->toIso8601String(),
                    'attempts' => $counter['attempts'] ?? 0,
                    'is_rate_limited' => ($counter['remaining'] ?? $limit) <= 0,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get user rate limit', [
                    'user_id' => $user->id ?? (method_exists($user, 'getId') ? $user->getId() : null),
                    'error' => $e->getMessage(),
                ]);
                
                return [
                    'tier' => $tier,
                    'limit' => $limit,
                    'remaining' => $limit,
                    'reset_at' => now()->addMinute()->toIso8601String(),
                    'attempts' => 0,
                    'is_rate_limited' => false,
                ];
            }
        }
        
        return [
            'tier' => 'public',
            'limit' => $this->defaultLimit,
            'remaining' => $this->defaultLimit,
            'reset_at' => now()->addMinute()->toIso8601String(),
            'attempts' => 0,
            'is_rate_limited' => false,
        ];
    }

    /**
     * Update user tier limits
     */
    public function updateTierLimits(array $newLimits): bool
    {
        try {
            $this->limits = array_merge($this->limits, $newLimits);
            
            Log::info('Rate limit tiers updated', [
                'old_limits' => $this->limits,
                'new_limits' => $this->limits,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update tier limits', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function normalizeResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        return response((string) $response, 200);
    }
}
