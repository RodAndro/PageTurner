<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for API rate limiting including
    | tiered limits, burst protection, and exemption rules.
    |
    */

    'enabled' => env('RATE_LIMITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Driver
    |--------------------------------------------------------------------------
    |
    | Configure the driver used for rate limiting.
    | Available drivers: redis, database, memory, file
    |
    */

    'driver' => env('RATE_LIMIT_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Tiered Rate Limits by User Role
    |--------------------------------------------------------------------------
    |
    | Configure different rate limits based on user roles.
    | Each role can have per-second, per-minute, and per-hour limits.
    |
    */

    'roles' => [
        'anonymous' => [
            'per_second' => env('RATE_LIMIT_ANONYMOUS_PER_SECOND', 2),
            'per_minute' => env('RATE_LIMIT_ANONYMOUS_PER_MINUTE', 30),
            'per_hour' => env('RATE_LIMIT_ANONYMOUS_PER_HOUR', 100),
            'per_day' => env('RATE_LIMIT_ANONYMOUS_PER_DAY', 1000),
        ],
        'standard' => [
            'per_second' => env('RATE_LIMIT_STANDARD_PER_SECOND', 10),
            'per_minute' => env('RATE_LIMIT_STANDARD_PER_MINUTE', 60),
            'per_hour' => env('RATE_LIMIT_STANDARD_PER_HOUR', 1000),
            'per_day' => env('RATE_LIMIT_STANDARD_PER_DAY', 10000),
        ],
        'customer' => [
            'per_second' => env('RATE_LIMIT_CUSTOMER_PER_SECOND', 5),
            'per_minute' => env('RATE_LIMIT_CUSTOMER_PER_MINUTE', 60),
            'per_hour' => env('RATE_LIMIT_CUSTOMER_PER_HOUR', 1000),
            'per_day' => env('RATE_LIMIT_CUSTOMER_PER_DAY', 10000),
        ],
        'premium' => [
            'per_second' => env('RATE_LIMIT_PREMIUM_PER_SECOND', 10),
            'per_minute' => env('RATE_LIMIT_PREMIUM_PER_MINUTE', 300),
            'per_hour' => env('RATE_LIMIT_PREMIUM_PER_HOUR', 5000),
            'per_day' => env('RATE_LIMIT_PREMIUM_PER_DAY', 50000),
        ],
        'admin' => [
            'per_second' => env('RATE_LIMIT_ADMIN_PER_SECOND', 20),
            'per_minute' => env('RATE_LIMIT_ADMIN_PER_MINUTE', 1000),
            'per_hour' => env('RATE_LIMIT_ADMIN_PER_HOUR', 10000),
            'per_day' => env('RATE_LIMIT_ADMIN_PER_DAY', 100000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint-Specific Rate Limits
    |--------------------------------------------------------------------------
    |
    | Configure custom rate limits for specific endpoints.
    | These override the role-based limits.
    |
    */

    'endpoints' => [
        // General API endpoints
        'api/books' => [
            'anonymous' => ['per_minute' => 20, 'per_hour' => 200],
            'customer' => ['per_minute' => 100, 'per_hour' => 1000],
            'premium' => ['per_minute' => 200, 'per_hour' => 2000],
            'admin' => ['per_minute' => 500, 'per_hour' => 5000],
        ],
        'api/books/search' => [
            'anonymous' => ['per_minute' => 10, 'per_hour' => 100],
            'customer' => ['per_minute' => 50, 'per_hour' => 500],
            'premium' => ['per_minute' => 100, 'per_hour' => 1000],
            'admin' => ['per_minute' => 200, 'per_hour' => 2000],
        ],
        'api/books/export' => [
            'anonymous' => ['per_hour' => 5, 'per_day' => 20],
            'customer' => ['per_hour' => 20, 'per_day' => 100],
            'premium' => ['per_hour' => 50, 'per_day' => 500],
            'admin' => ['per_hour' => 100, 'per_day' => 1000],
        ],
        'api/orders' => [
            'customer' => ['per_minute' => 50, 'per_hour' => 500],
            'premium' => ['per_minute' => 100, 'per_hour' => 1000],
            'admin' => ['per_minute' => 200, 'per_hour' => 2000],
        ],
        'api/admin/backup' => [
            'admin' => ['per_hour' => 10, 'per_day' => 50],
        ],
        'api/admin/users' => [
            'admin' => ['per_minute' => 100, 'per_hour' => 1000],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Burst Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure burst protection to prevent rapid-fire requests.
    |
    */

    'burst_protection' => [
        'enabled' => env('RATE_LIMIT_BURST_ENABLED', true),
        'max_burst_requests' => env('RATE_LIMIT_MAX_BURST', 10),
        'burst_window_seconds' => env('RATE_LIMIT_BURST_WINDOW', 1),
        'penalty_seconds' => env('RATE_LIMIT_BURST_PENALTY', 30),
        'progressive_penalty' => env('RATE_LIMIT_PROGRESSIVE_PENALTY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Exemptions
    |--------------------------------------------------------------------------
    |
    | Configure which users, roles, or endpoints are exempt from rate limiting.
    |
    */

    'exempt' => [
        'roles' => [
            'super_admin' => env('RATE_LIMIT_EXEMPT_SUPER_ADMIN', true),
        ],
        'users' => [
            // Array of user IDs exempt from rate limiting
            'exempt_user_ids' => env('RATE_LIMIT_EXEMPT_USER_IDS', ''),
        ],
        'paths' => [
            // Paths exempt from rate limiting
            'health' => env('RATE_LIMIT_EXEMPT_HEALTH', true),
            'status' => env('RATE_LIMIT_EXEMPT_STATUS', true),
            'ping' => env('RATE_LIMIT_EXEMPT_PING', true),
            'metrics' => env('RATE_LIMIT_EXEMPT_METRICS', false),
        ],
        'ip_ranges' => [
            // IP ranges exempt from rate limiting
            '127.0.0.1/32', // localhost
            '10.0.0.0/8',    // Private network
            '192.168.0.0/16', // Private network
            '172.16.0.0/12',  // Private network
        ],
        'admin' => [
            // Admin-specific exemptions
            'backup' => env('RATE_LIMIT_EXEMPT_ADMIN_BACKUP', false),
            'logs' => env('RATE_LIMIT_EXEMPT_ADMIN_LOGS', false),
            'analytics' => env('RATE_LIMIT_EXEMPT_ADMIN_ANALYTICS', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where rate limit data is stored.
    |
    */

    'storage' => [
        'redis' => [
            'connection' => env('RATE_LIMIT_REDIS_CONNECTION', 'default'),
            'prefix' => env('RATE_LIMIT_REDIS_PREFIX', 'rate_limit:'),
            'ttl_seconds' => env('RATE_LIMIT_REDIS_TTL', 3600), // 1 hour
        ],
        'database' => [
            'connection' => env('RATE_LIMIT_DB_CONNECTION', null),
            'table' => env('RATE_LIMIT_DB_TABLE', 'rate_limits'),
            'cleanup_interval_minutes' => env('RATE_LIMIT_DB_CLEANUP', 60),
        ],
        'file' => [
            'path' => env('RATE_LIMIT_FILE_PATH', storage_path('framework/rate-limits')),
            'cleanup_interval_minutes' => env('RATE_LIMIT_FILE_CLEANUP', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Response Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how rate limit responses are formatted.
    |
    */

    'responses' => [
        'headers' => [
            'enabled' => env('RATE_LIMIT_HEADERS_ENABLED', true),
            'limit_header' => 'X-RateLimit-Limit',
            'remaining_header' => 'X-RateLimit-Remaining',
            'reset_header' => 'X-RateLimit-Reset',
            'retry_after_header' => 'Retry-After',
        ],
        'body' => [
            'include_details' => env('RATE_LIMIT_INCLUDE_DETAILS', true),
            'include_retry_after' => env('RATE_LIMIT_INCLUDE_RETRY', true),
            'format' => env('RATE_LIMIT_RESPONSE_FORMAT', 'json'), // json, xml, text
        ],
        'status_code' => env('RATE_LIMIT_STATUS_CODE', 429),
        'message' => env('RATE_LIMIT_MESSAGE', 'Too Many Requests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure monitoring and logging for rate limiting.
    |
    */

    'monitoring' => [
        'enabled' => env('RATE_LIMIT_MONITORING_ENABLED', true),
        'log_all_requests' => env('RATE_LIMIT_LOG_ALL', false),
        'log_violations' => env('RATE_LIMIT_LOG_VIOLATIONS', true),
        'log_headers' => env('RATE_LIMIT_LOG_HEADERS', true),
        'track_ip_addresses' => env('RATE_LIMIT_TRACK_IPS', true),
        'track_user_agents' => env('RATE_LIMIT_TRACK_UA', true),
        'alert_threshold_percent' => env('RATE_LIMIT_ALERT_THRESHOLD', 80), // Alert when 80% of limit reached
        'analytics_retention_days' => env('RATE_LIMIT_ANALYTICS_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database settings for rate limit tracking.
    |
    */

    'database' => [
        'table' => 'api_rate_limits',
        'columns' => [
            'id' => 'bigIncrements',
            'user_id' => 'unsignedBigInteger',
            'ip_address' => 'string',
            'endpoint' => 'string',
            'user_agent' => 'text',
            'requests_count' => 'integer',
            'is_throttled' => 'boolean',
            'throttled_at' => 'timestamp',
            'status' => 'string',
            'period' => 'string',
            'limit' => 'integer',
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
        ],
        'indexes' => [
            'user_id' => true,
            'ip_address' => true,
            'endpoint' => true,
            'throttled_at' => true,
            'created_at' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance optimization settings.
    |
    */

    'performance' => [
        'cache_enabled' => env('RATE_LIMIT_CACHE_ENABLED', true),
        'cache_ttl_seconds' => env('RATE_LIMIT_CACHE_TTL', 60),
        'batch_inserts' => env('RATE_LIMIT_BATCH_INSERTS', true),
        'batch_size' => env('RATE_LIMIT_BATCH_SIZE', 100),
        'async_logging' => env('RATE_LIMIT_ASYNC_LOGGING', true),
        'queue_connection' => env('RATE_LIMIT_QUEUE_CONNECTION', 'default'),
        'queue_name' => env('RATE_LIMIT_QUEUE_NAME', 'rate-limiting'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for rate limiting.
    |
    */

    'security' => [
        'ip_whitelist_enabled' => env('RATE_LIMIT_IP_WHITELIST_ENABLED', false),
        'ip_whitelist' => env('RATE_LIMIT_IP_WHITELIST', ''),
        'ip_blacklist_enabled' => env('RATE_LIMIT_IP_BLACKLIST_ENABLED', true),
        'ip_blacklist' => env('RATE_LIMIT_IP_BLACKLIST', ''),
        'user_agent_filtering' => env('RATE_LIMIT_UA_FILTERING', false),
        'blocked_user_agents' => env('RATE_LIMIT_BLOCKED_UA', ''),
        'geo_blocking_enabled' => env('RATE_LIMIT_GEO_BLOCKING', false),
        'blocked_countries' => env('RATE_LIMIT_BLOCKED_COUNTRIES', ''),
        'require_https' => env('RATE_LIMIT_REQUIRE_HTTPS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing rate limiting functionality.
    |
    */

    'testing' => [
        'enabled' => env('RATE_LIMIT_TESTING', false),
        'mock_storage' => env('RATE_LIMIT_MOCK_STORAGE', false),
        'simulate_failures' => env('RATE_LIMIT_SIMULATE_FAILURES', false),
        'failure_rate' => env('RATE_LIMIT_FAILURE_RATE', 0.1), // 10% failure rate
        'test_requests_per_second' => env('RATE_LIMIT_TEST_RPS', 100),
        'test_duration_seconds' => env('RATE_LIMIT_TEST_DURATION', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API-specific rate limiting settings.
    |
    */

    'api' => [
        'versioning' => [
            'enabled' => env('RATE_LIMIT_API_VERSIONING', false),
            'header' => 'X-API-Version',
            'default_version' => 'v1',
        ],
        'authentication' => [
            'require_api_key' => env('RATE_LIMIT_REQUIRE_API_KEY', false),
            'api_key_header' => env('RATE_LIMIT_API_KEY_HEADER', 'X-API-Key'),
            'rate_limit_by_api_key' => env('RATE_LIMIT_BY_API_KEY', false),
        ],
        'documentation' => [
            'include_in_docs' => env('RATE_LIMIT_DOCS_INCLUDE', true),
            'docs_url' => env('RATE_LIMIT_DOCS_URL', '/api/docs/rate-limits'),
            'contact_email' => env('RATE_LIMIT_CONTACT_EMAIL', 'api-support@example.com'),
        ],
    ],
];
