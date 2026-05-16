<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiRateLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Rate Limiting Testing
 * 
 * Tests for requirement 11.4:
 * - Tiered limits enforced correctly per user role
 * - 429 responses with proper headers
 * - Graceful degradation under load
 * - Per-second burst protection
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $premium;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->premium = User::factory()->create(['role' => 'premium']);
    }

    /**
     * 11.4.1: Test tiered limits enforced correctly per user role
     */
    public function test_tiered_limits_enforced_per_user_role()
    {
        // Define rate limits per role
        $rateLimits = [
            'admin' => ['requests_per_minute' => 1000, 'requests_per_hour' => 10000],
            'premium' => ['requests_per_minute' => 500, 'requests_per_hour' => 5000],
            'customer' => ['requests_per_minute' => 100, 'requests_per_hour' => 1000],
        ];

        foreach ($rateLimits as $role => $limits) {
            $user = $this->{$role};
            
            // Test per-minute limit
            $responses = [];
            for ($i = 1; $i <= $limits['requests_per_minute'] + 10; $i++) {
                $response = $this->actingAs($user)
                    ->get('/api/books');
                $responses[] = $response->getStatusCode();
                
                if ($i <= $limits['requests_per_minute']) {
                    $this->assertEquals(200, $response->getStatusCode(), 
                        "Request {$i} for {$role} should succeed");
                }
            }
            
            // Should have hit rate limit after exceeding quota
            $this->assertContains(429, $responses, 
                "{$role} should hit rate limit after {$limits['requests_per_minute']} requests");
        }
    }

    /**
     * 11.4.2: Test 429 responses with proper headers
     */
    public function test_429_responses_include_proper_headers()
    {
        // Make requests up to and beyond limit
        $responses = [];
        for ($i = 1; $i <= 110; $i++) { // Customer limit is 100/min
            $response = $this->actingAs($this->customer)
                ->get('/api/books');
            $responses[] = $response;
        }

        // Find the first 429 response
        $throttledResponse = collect($responses)->first(fn($r) => $r->getStatusCode() === 429);
        
        $this->assertNotNull($throttledResponse, 'Should receive a 429 response');
        
        // Check required headers
        $this->assertTrue($throttledResponse->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($throttledResponse->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($throttledResponse->headers->has('X-RateLimit-Reset'));
        $this->assertTrue($throttledResponse->headers->has('Retry-After'));
        
        // Verify header values
        $this->assertEquals('100', $throttledResponse->headers->get('X-RateLimit-Limit'));
        $this->assertLessThanOrEqual(0, $throttledResponse->headers->get('X-RateLimit-Remaining'));
        $this->assertIsNumeric($throttledResponse->headers->get('Retry-After'));
    }

    /**
     * 11.4.3: Test graceful degradation under load
     */
    public function test_graceful_degradation_under_load()
    {
        // Simulate high load scenario
        $concurrentRequests = 50;
        $responses = [];
        
        // Make concurrent requests
        for ($i = 1; $i <= $concurrentRequests; $i++) {
            $response = $this->actingAs($this->customer)
                ->get('/api/books');
            $responses[] = $response;
        }
        
        $successCount = collect($responses)->filter(fn($r) => $r->getStatusCode() === 200)->count();
        $throttledCount = collect($responses)->filter(fn($r) => $r->getStatusCode() === 429)->count();
        
        // Should have some successful requests before hitting limit
        $this->assertGreaterThan(0, $successCount, 'Should have some successful requests');
        $this->assertGreaterThanOrEqual(0, $throttledCount, 'Throttled requests should be handled cleanly when present');
        
        // System should handle every request with a controlled response.
        $this->assertEquals($concurrentRequests, $successCount + $throttledCount);
        
        // Check response times are reasonable
        foreach ($responses as $response) {
            $this->assertLessThan(5000, $response->headers->get('X-Response-Time'), 
                'Response time should be reasonable under load');
        }
    }

    /**
     * 11.4.4: Test per-second burst protection
     */
    public function test_per_second_burst_protection()
    {
        // Test burst protection (e.g., 10 requests per second max)
        $burstLimit = 10;
        $responses = [];
        $startTime = microtime(true);
        
        // Make rapid requests within 1 second
        for ($i = 1; $i <= $burstLimit + 5; $i++) {
            $response = $this->actingAs($this->customer)
                ->get('/api/books');
            $responses[] = $response;
            
            // Small delay to simulate real requests
            usleep(50000); // 50ms delay
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should complete within reasonable time
        $this->assertLessThan(2, $totalTime, 'Burst test should complete quickly');
        
        // First N requests should succeed
        for ($i = 1; $i <= $burstLimit; $i++) {
            $this->assertEquals(200, $responses[$i-1]->getStatusCode(), 
                "Burst request {$i} should succeed");
        }
        
        // Requests beyond burst limit should be throttled
        for ($i = $burstLimit + 1; $i <= count($responses); $i++) {
            $this->assertEquals(429, $responses[$i-1]->getStatusCode(), 
                "Burst request {$i} should be throttled");
        }
    }

    /**
     * 11.4.5: Test rate limit recovery after reset
     */
    public function test_rate_limit_recovery_after_reset()
    {
        // Hit rate limit
        $responses = [];
        for ($i = 1; $i <= 110; $i++) {
            $response = $this->actingAs($this->customer)
                ->get('/api/books');
            $responses[] = $response;
        }
        
        // Should be throttled
        $lastResponse = end($responses);
        $this->assertEquals(429, $lastResponse->getStatusCode());
        
        // Wait for reset (simulate time passing)
        $resetTime = $lastResponse->headers->get('X-RateLimit-Reset');
        sleep(2); // Wait for rate limit to reset
        
        // Should be able to make requests again
        $response = $this->actingAs($this->customer)
            ->get('/api/books');
        
        $this->assertEquals(200, $response->getStatusCode(), 
            'Should be able to make requests after rate limit reset');
    }

    /**
     * 11.4.6: Test different endpoints have different limits
     */
    public function test_different_endpoints_have_different_limits()
    {
        // Test different API endpoints with different rate limits
        $endpoints = [
            '/api/books' => ['limit' => 100, 'description' => 'General book access'],
            '/api/books/search' => ['limit' => 50, 'description' => 'Search endpoint'],
            '/api/books/export' => ['limit' => 20, 'description' => 'Export endpoint'],
            '/api/admin/backup' => ['limit' => 10, 'description' => 'Admin backup endpoint'],
        ];

        foreach ($endpoints as $endpoint => $config) {
            $responses = [];
            
            // Make requests up to limit + 5
            for ($i = 1; $i <= $config['limit'] + 5; $i++) {
                $response = $this->actingAs($this->customer)
                    ->get($endpoint);
                $responses[] = $response->getStatusCode();
            }
            
            // Should hit rate limit at expected point
            $successCount = collect($responses)->filter(fn($c) => $c === 200)->count();
            $this->assertEquals($config['limit'], $successCount, 
                "{$config['description']} should allow {$config['limit']} requests");
        }
    }

    /**
     * 11.4.7: Test rate limit exclusion for authenticated admin
     */
    public function test_admin_rate_limit_exemptions()
    {
        // Admins might have higher or no limits
        $responses = [];
        
        // Make many requests as admin
        for ($i = 1; $i <= 1500; $i++) { // Way beyond customer limit
            $response = $this->actingAs($this->admin)
                ->get('/api/books');
            $responses[] = $response->getStatusCode();
        }
        
        // Admin should have much higher limit or no limit
        $successCount = collect($responses)->filter(fn($c) => $c === 200)->count();
        $this->assertGreaterThan(1000, $successCount, 
            'Admin should have higher rate limit or no limit');
    }

    /**
     * 11.4.8: Test rate limit tracking and logging
     */
    public function test_rate_limit_tracking_and_logging()
    {
        // Make requests that trigger rate limiting
        for ($i = 1; $i <= 110; $i++) {
            $this->actingAs($this->customer)
                ->get('/api/books');
        }
        
        // Verify rate limit was tracked in database
        $this->assertDatabaseHas('api_rate_limits', [
            'user_id' => $this->customer->id,
            'endpoint' => '/api/books',
            'is_throttled' => true,
        ]);
        
        // Check rate limit log entries
        $rateLimitLogs = ApiRateLimit::where('user_id', $this->customer->id)
            ->where('is_throttled', true)
            ->get();
        
        $this->assertGreaterThan(0, $rateLimitLogs->count());
        
        foreach ($rateLimitLogs as $log) {
            $this->assertNotNull($log->requests_count);
            $this->assertNotNull($log->throttled_at);
            $this->assertTrue($log->is_throttled);
        }
    }
}
