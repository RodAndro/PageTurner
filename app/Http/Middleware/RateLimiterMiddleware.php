<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use App\Models\ApiRateLimit;
use Carbon\Carbon;

class RateLimiterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        $user = $request->user();
        
        // Get rate limits based on user role
        $limits = $this->getRateLimits($user);
        
        // Check if user is exempt from rate limiting
        if ($this->isExempt($user, $request)) {
            return $next($request);
        }

        // Check different rate limits (per minute, per hour, per second)
        $limitChecks = [
            'per_second' => $this->checkRateLimit($key, $limits['per_second'], 1, $user),
            'per_minute' => $this->checkRateLimit($key, $limits['per_minute'], 60, $user),
            'per_hour' => $this->checkRateLimit($key, $limits['per_hour'], 3600, $user),
        ];

        // Find the most restrictive limit that was hit
        $blockedBy = null;
        foreach ($limitChecks as $period => $result) {
            if (!$result['allowed']) {
                $blockedBy = $period;
                break;
            }
        }

        if ($blockedBy) {
            $this->logRateLimitHit($user, $request, $blockedBy, $limits[$blockedBy]);
            
            return $this->buildRateLimitResponse($limits[$blockedBy], $blockedBy);
        }

        $response = $next($request);

        // Add rate limit headers to successful responses
        $response = $this->addRateLimitHeaders($response, $key, $limits);

        return $response;
    }

    /**
     * Get rate limits based on user role
     */
    protected function getRateLimits($user): array
    {
        $role = $user ? $user->role : 'anonymous';
        
        $baseLimits = [
            'anonymous' => [
                'per_second' => 2,
                'per_minute' => 10,
                'per_hour' => 100,
            ],
            'customer' => [
                'per_second' => 5,
                'per_minute' => 100,
                'per_hour' => 1000,
            ],
            'premium' => [
                'per_second' => 10,
                'per_minute' => 500,
                'per_hour' => 5000,
            ],
            'admin' => [
                'per_second' => 20,
                'per_minute' => 1000,
                'per_hour' => 10000,
            ],
        ];

        // Apply custom configurations if available
        $customLimits = config('rate-limits.roles.' . $role, []);
        
        return array_merge($baseLimits[$role], $customLimits);
    }

    /**
     * Check if user or endpoint is exempt from rate limiting
     */
    protected function isExempt($user, $request): bool
    {
        // Admins might be exempt from certain endpoints
        if ($user && $user->role === 'admin') {
            $exemptEndpoints = config('rate-limits.exempt.admin', []);
            if (in_array($request->path(), $exemptEndpoints)) {
                return true;
            }
        }

        // Health check endpoints are typically exempt
        $exemptPaths = config('rate-limits.exempt.paths', [
            'health',
            'status',
            'ping',
        ]);

        foreach ($exemptPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check rate limit for a specific period
     */
    protected function checkRateLimit(string $key, int $maxAttempts, int $decaySeconds, $user): array
    {
        $executed = RateLimiter::attempt($key, $maxAttempts, $decaySeconds);
        
        $remaining = $maxAttempts - RateLimiter::attempts($key);
        $resetTime = Carbon::now()->addSeconds($decaySeconds);

        return [
            'allowed' => $executed,
            'remaining' => max(0, $remaining),
            'reset_time' => $resetTime,
        ];
    }

    /**
     * Generate request signature for rate limiting
     */
    protected function resolveRequestSignature($request): string
    {
        $user = $request->user();
        $ip = $request->ip();
        $path = $request->path();
        
        // Use user ID if authenticated, otherwise use IP
        $identifier = $user ? 'user:' . $user->id : 'ip:' . $ip;
        
        return sha1($identifier . '|' . $path);
    }

    /**
     * Build rate limit response with proper headers
     */
    protected function buildRateLimitResponse(array $limits, string $period): \Illuminate\Http\Response
    {
        $retryAfter = $this->calculateRetryAfter($limits, $period);
        
        return Response::json([
            'message' => 'Too many requests',
            'error' => 'rate_limit_exceeded',
            'limit' => $limits[$period],
            'period' => $period,
            'retry_after' => $retryAfter->timestamp,
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => (string) $limits[$period],
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => $retryAfter->toIso8601String(),
            'Retry-After' => (string) $retryAfter->timestamp,
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, string $key, array $limits): \Illuminate\Http\Response
    {
        $remaining = max(0, $limits['per_minute'] - RateLimiter::attempts($key . ':minute'));
        $resetTime = Carbon::now()->addMinute();

        return $response->withHeaders([
            'X-RateLimit-Limit' => (string) $limits['per_minute'],
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset' => $resetTime->toIso8601String(),
        ]);
    }

    /**
     * Calculate retry after timestamp
     */
    protected function calculateRetryAfter(array $limits, string $period): Carbon
    {
        $multipliers = [
            'per_second' => 1,
            'per_minute' => 60,
            'per_hour' => 3600,
        ];

        $seconds = ($multipliers[$period] ?? 60) * 2; // Wait 2x the period
        return Carbon::now()->addSeconds($seconds);
    }

    /**
     * Log rate limit hits for analytics
     */
    protected function logRateLimitHit($user, $request, string $period, int $limit): void
    {
        ApiRateLimit::create([
            'user_id' => $user ? $user->id : null,
            'ip_address' => $request->ip(),
            'endpoint' => $request->path(),
            'user_agent' => $request->userAgent(),
            'requests_count' => $limit + 1, // Exceeded by 1
            'is_throttled' => true,
            'throttled_at' => now(),
            'status' => 'throttled',
            'period' => $period,
            'limit' => $limit,
        ]);
    }
}
