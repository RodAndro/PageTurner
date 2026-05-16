<?php

namespace Database\Seeders;

use App\Models\ApiRateLimit;
use Illuminate\Database\Seeder;

class ApiRateLimitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample API rate limit records for testing.
     */
    public function run(): void
    {
        // User within limits
        ApiRateLimit::create([
            'user_id' => 1,
            'ip_address' => '192.168.1.100',
            'endpoint' => '/api/books',
            'method' => 'GET',
            'requests_count' => 45,
            'limit' => 100,
            'remaining' => 55,
            'reset_at_timestamp' => time() + 3600,
            'is_throttled' => false,
            'status' => 'allowed',
            'reason' => null,
            'throttled_until' => null,
        ]);

        // User approaching limit
        ApiRateLimit::create([
            'user_id' => 2,
            'ip_address' => '192.168.1.101',
            'endpoint' => '/api/orders',
            'method' => 'POST',
            'requests_count' => 95,
            'limit' => 100,
            'remaining' => 5,
            'reset_at_timestamp' => time() + 1800,
            'is_throttled' => false,
            'status' => 'allowed',
            'reason' => null,
            'throttled_until' => null,
        ]);

        // User throttled - exceeded limit
        ApiRateLimit::create([
            'user_id' => 3,
            'ip_address' => '192.168.1.102',
            'endpoint' => '/api/categories',
            'method' => 'GET',
            'requests_count' => 150,
            'limit' => 100,
            'remaining' => 0,
            'reset_at_timestamp' => time() + 900,
            'is_throttled' => true,
            'status' => 'throttled',
            'reason' => 'Rate limit exceeded for GET /api/categories',
            'throttled_until' => now()->addMinutes(15),
        ]);

        // Blocked IP address
        ApiRateLimit::create([
            'user_id' => null,
            'ip_address' => '203.0.113.45',
            'endpoint' => null,
            'method' => 'GET',
            'requests_count' => 5000,
            'limit' => 100,
            'remaining' => 0,
            'reset_at_timestamp' => null,
            'is_throttled' => true,
            'status' => 'blocked',
            'reason' => 'Suspected brute force attack - excessive requests from single IP',
            'throttled_until' => now()->addHours(24),
        ]);

        // Multiple endpoints for same user
        ApiRateLimit::create([
            'user_id' => 1,
            'ip_address' => '192.168.1.100',
            'endpoint' => '/api/reviews',
            'method' => 'POST',
            'requests_count' => 30,
            'limit' => 50,
            'remaining' => 20,
            'reset_at_timestamp' => time() + 3600,
            'is_throttled' => false,
            'status' => 'allowed',
            'reason' => null,
            'throttled_until' => null,
        ]);

        // User with burst of requests
        ApiRateLimit::create([
            'user_id' => 4,
            'ip_address' => '192.168.1.103',
            'endpoint' => '/api/search',
            'method' => 'GET',
            'requests_count' => 89,
            'limit' => 100,
            'remaining' => 11,
            'reset_at_timestamp' => time() + 600,
            'is_throttled' => false,
            'status' => 'allowed',
            'reason' => null,
            'throttled_until' => null,
        ]);

        // Recently recovered from throttling
        ApiRateLimit::create([
            'user_id' => 5,
            'ip_address' => '192.168.1.104',
            'endpoint' => '/api/export',
            'method' => 'POST',
            'requests_count' => 8,
            'limit' => 20,
            'remaining' => 12,
            'reset_at_timestamp' => time() + 1800,
            'is_throttled' => false,
            'status' => 'allowed',
            'reason' => 'Previously throttled but now within limits',
            'throttled_until' => null,
        ]);
    }
}
