<?php

namespace Tests\Unit;

use App\Http\Middleware\TieredRateLimitMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    protected TieredRateLimitMiddleware $middleware;
    protected int $testUserId = 12345;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TieredRateLimitMiddleware();
        $this->middleware->resetManualCounters();
        
        \Illuminate\Support\Facades\RateLimiter::clear('rate_limit:ip:127.0.0.1:public');
    }

    /**
     * Test tier-based rate limiting
     */
    public function test_tier_based_rate_limiting(): void
    {
        // Test public user limit
        $request = $this->createRequest('public');
        $response = $this->middleware->handle($request, fn() => 'next');
        
        $this->assertEquals(200, $response->getStatusCode(), 'First request should pass');
        $this->assertEquals(30, $response->headers->get('X-RateLimit-Limit'), 'Public user should have 30 requests per minute');
        $this->assertEquals(29, $response->headers->get('X-RateLimit-Remaining'), 'Should have 29 requests remaining');
    }

    /**
     * Test premium user limit
     */
    public function test_premium_user_rate_limiting(): void
    {
        $user = new class {
            public function getRole() { return 'premium'; }
            public function getSubscriptionTier() { return 'premium'; }
            public function getId() { return 12346; }
        };

        $request = $this->createRequest($user);
        $response = $this->middleware->handle($request, fn() => 'next');
        
        $this->assertEquals(200, $response->getStatusCode(), 'First request should pass');
        $this->assertEquals(300, $response->headers->get('X-RateLimit-Limit'), 'Premium user should have 300 requests per minute');
        $this->assertEquals(299, $response->headers->get('X-RateLimit-Remaining'), 'Should have 299 requests remaining');
    }

    /**
     * Test admin exemption
     */
    public function test_admin_exemption(): void
    {
        $user = new class {
            public function getRole() { return 'admin'; }
            public function getSubscriptionTier() { return 'premium'; }
            public function getId() { return 12347; }
        };

        $request = $this->createRequest($user);
        $response = $this->middleware->handle($request, fn() => 'next');
        
        // Admin should not be rate limited on admin routes
        $this->assertEquals(200, $response->getStatusCode(), 'Admin should not be rate limited');
    }

    /**
     * Test health check exemption
     */
    public function test_health_check_exemption(): void
    {
        $request = $this->createRequest(null, 'api/health');
        $response = $this->middleware->handle($request, fn() => 'next');
        
        $this->assertEquals(200, $response->getStatusCode(), 'Health check should not be rate limited');
    }

    /**
     * Test IP-based rate limiting
     */
    public function test_ip_based_rate_limiting(): void
    {
        $request = $this->createRequest(null, 'api/books');
        $response = $this->middleware->handle($request, fn() => 'next');
        
        $this->assertEquals(200, $response->getStatusCode(), 'First request should pass');
        $this->assertEquals(30, $response->headers->get('X-RateLimit-Limit'), 'IP should have 30 requests per minute');
    }

    /**
     * Test rate limit counters
     */
    public function test_rate_limit_counters(): void
    {
        // Set initial counters
        $this->middleware->setRateLimitCounters('ip:test', 30);
        
        $request = $this->createRequest(null, 'api/books');
        $response = $this->middleware->handle($request, fn() => 'next');
        
        // First request should consume one attempt
        $this->assertEquals(200, $response->getStatusCode(), 'First request should pass');
        $this->assertEquals(29, $response->headers->get('X-RateLimit-Remaining'), 'Should have 29 requests remaining');
        
        // Second request should consume another attempt
        $response = $this->middleware->handle($request, fn() => 'next');
        $this->assertEquals(200, $response->getStatusCode(), 'Second request should pass');
        $this->assertEquals(28, $response->headers->get('X-RateLimit-Remaining'), 'Should have 28 requests remaining');
    }

    /**
     * Test rate limit statistics
     */
    public function test_rate_limit_statistics(): void
    {
        // Set up test data
        $this->middleware->setRateLimitCounters('user:12345', 30);
        $this->middleware->setRateLimitCounters('user:12346', 60);
        $this->middleware->setRateLimitCounters('user:12347', 300);
        
        $stats = $this->middleware->getRateLimitStats();
        
        $this->assertArrayHasKey('total_keys', $stats, 'Should include total keys');
        $this->assertEquals(3, $stats['total_keys'], 'Should have 3 user keys');
        
        $this->assertArrayHasKey('by_tier', $stats, 'Should include tier breakdown');
        $this->assertArrayHasKey('public', $stats['by_tier'], 'Should include public tier');
        $this->assertArrayHasKey('premium', $stats['by_tier'], 'Should include premium tier');
        $this->assertArrayHasKey('admin', $stats['by_tier'], 'Should include admin tier');
        
        // Verify tier data
        foreach ($stats['by_tier'] as $tier => $tierStats) {
            $this->assertArrayHasKey('active_keys', $tierStats, "Should include active keys for {$tier}");
            $this->assertArrayHasKey('total_requests', $tierStats, "Should include total requests for {$tier}");
            $this->assertArrayHasKey('rate_limited_requests', $tierStats, "Should include rate limited requests for {$tier}");
        }
    }

    /**
     * Test cache clearing
     */
    public function test_cache_clearing(): void
    {
        $user = new class {
            public function getId() { return 12345; }
        };

        // Set up rate limit
        $this->middleware->setRateLimitCounters('user:12345', 30);
        
        // Clear user's rate limit
        $cleared = $this->middleware->clearRateLimit($user);
        $this->assertTrue($cleared, 'Should successfully clear user rate limit');
        
        // Verify counters are reset
        $userStats = $this->middleware->getUserRateLimit($user);
        $this->assertEquals(30, $userStats['remaining'], 'Should have full limit after clear');
        $this->assertEquals(0, $userStats['attempts'], 'Should have 0 attempts after clear');
    }

    /**
     * Test tier limit updates
     */
    public function test_tier_limit_updates(): void
    {
        $newLimits = [
            'public' => 60, // Increased from 30
            'standard' => 120, // Increased from 60
            'premium' => 600, // Increased from 300
        ];
        
        $updated = $this->middleware->updateTierLimits($newLimits);
        $this->assertTrue($updated, 'Should successfully update tier limits');
        
        // Verify new limits are applied
        $stats = $this->middleware->getRateLimitStats();
        $this->assertEquals(60, $stats['by_tier']['public']['limit'], 'Public limit should be updated to 60');
        $this->assertEquals(120, $stats['by_tier']['standard']['limit'], 'Standard limit should be updated to 120');
        $this->assertEquals(600, $stats['by_tier']['premium']['limit'], 'Premium limit should be updated to 600');
    }

    /**
     * Test user rate limit retrieval
     */
    public function test_user_rate_limit_retrieval(): void
    {
        $user = new class {
            public function getId() { return 12345; }
            public function getSubscriptionTier() { return 'standard'; }
        public function getRole() { return 'user'; }
        };

        // Set up rate limit
        $this->middleware->setRateLimitCounters('user:12345', 60);
        
        $userStats = $this->middleware->getUserRateLimit($user);
        
        $this->assertEquals('standard', $userStats['tier'], 'Should return standard tier');
        $this->assertEquals(60, $userStats['limit'], 'Should return standard limit');
        $this->assertEquals(60, $userStats['remaining'], 'Should return full remaining');
        $this->assertEquals(0, $userStats['attempts'], 'Should return 0 attempts');
        $this->assertFalse($userStats['is_rate_limited'], 'Should not be rate limited');
    }

    /**
     * Create mock request for testing
     */
    protected function createRequest($user = null, string $path = 'api/books'): Request
    {
        $request = Request::create("/{$path}", 'GET');
        $request->setUserResolver(fn() => $user);
        $request->headers->set('User-Agent', 'Test-Agent');
        
        return $request;
    }
}
