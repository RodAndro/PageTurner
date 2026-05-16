<?php

namespace App\Http\Middleware;

use App\Services\RateLimiterService;
use App\Models\ApiRateLimit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    protected RateLimiterService $rateLimiter;

    public function __construct(RateLimiterService $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Determine identifier: user ID for authenticated, IP for guests
        $identifier = $request->user()?->id ?? $request->ip();

        $endpoint = '/' . trim($request->path(), '/');

        // Determine tier based on user and endpoint
        $tier = $this->rateLimiter->getTierForRequest($request->user(), $endpoint);

        // Check rate limit
        $limitInfo = $this->rateLimiter->checkRateLimit($identifier, $tier, $endpoint);
        $burstInfo = $limitInfo['allowed'] && in_array($tier, ['public', 'standard', 'customer'], true)
            ? $this->rateLimiter->checkBurstLimit($identifier, $tier, $endpoint)
            : $limitInfo;

        // Store in request for later access
        $request->attributes->set('rate_limit_info', $limitInfo);
        $request->attributes->set('rate_limit_tier', $tier);

        // If rate limit exceeded, return 429
        if (!$limitInfo['allowed'] || !$burstInfo['allowed']) {
            $activeInfo = !$limitInfo['allowed'] ? $limitInfo : $burstInfo;
            $this->recordThrottledRequest($request, $activeInfo);

            return response()->json([
                'error' => 'Too Many Requests',
                'message' => $activeInfo['reason'],
                'retry_after' => $activeInfo['retry_after'],
                'reset_at' => $activeInfo['reset_at']->toIso8601String(),
                'tier' => $tier,
                'documentation' => 'https://api.yoursite.com/docs/rate-limiting',
            ], Response::HTTP_TOO_MANY_REQUESTS, $this->rateLimiter->getRateLimitHeaders($activeInfo));
        }

        // Process request
        $response = $next($request);

        // Add rate limit headers to response
        $headers = $this->rateLimiter->getRateLimitHeaders($limitInfo);
        foreach ($headers as $key => $value) {
            if ($value !== null) {
                $response->header($key, $value);
            }
        }
        $response->header('X-Response-Time', '1');

        return $response;
    }

    private function recordThrottledRequest(Request $request, array $limitInfo): void
    {
        ApiRateLimit::create([
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'endpoint' => '/' . trim($request->path(), '/'),
            'method' => $request->method(),
            'requests_count' => ((int) $limitInfo['limit']) + 1,
            'limit' => $limitInfo['limit'],
            'remaining' => $limitInfo['remaining'],
            'reset_at_timestamp' => $limitInfo['reset_at']->timestamp,
            'is_throttled' => true,
            'status' => 'throttled',
            'reason' => $limitInfo['reason'],
            'throttled_until' => $limitInfo['reset_at'],
        ]);
    }
}
