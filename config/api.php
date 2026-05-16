<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the rate limiting tiers and behavior for your API
    |
    */

    'rate_limiter' => [
        'enabled' => env('API_RATE_LIMIT_ENABLED', true),
        'driver' => env('API_RATE_LIMIT_DRIVER', 'cache'), // cache or redis

        'tiers' => [
            'public' => [
                'requests_per_minute' => 30,
                'description' => 'General browsing, book search',
            ],
            'standard' => [
                'requests_per_minute' => 60,
                'description' => 'Authenticated API access',
            ],
            'premium' => [
                'requests_per_minute' => 300,
                'description' => 'High-volume API access',
            ],
            'admin' => [
                'requests_per_minute' => 1000,
                'description' => 'Administrative operations',
            ],
            'auth' => [
                'requests_per_minute' => 10,
                'description' => 'Login, registration, password reset',
            ],
        ],

        // Per-second granularity for burst protection
        'burst_protection' => true,

        // Define auth endpoints that get stricter rate limiting
        'auth_endpoints' => [
            'login', 'register', 'password.email', 'password.reset',
            'password.forgot', 'auth.verify', 'auth.confirm',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Response Transformation
    |--------------------------------------------------------------------------
    |
    | Configure automatic response transformation (snake_case to camelCase)
    |
    */

    'response_transformation' => [
        'enabled' => env('API_TRANSFORM_RESPONSE', true),
        'snake_to_camel' => true, // Convert snake_case to camelCase
        'allow_disable' => true, // Allow clients to disable via ?_no_transform=true
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Filtering
    |--------------------------------------------------------------------------
    |
    | Allow clients to specify which fields to return
    | Usage: GET /api/books?fields=id,title,price
    |
    */

    'field_filtering' => [
        'enabled' => env('API_FIELD_FILTERING', true),
        'parameter' => 'fields', // Query parameter name
        'separator' => ',', // Field separator
    ],

    /*
    |--------------------------------------------------------------------------
    | Cursor-Based Pagination
    |--------------------------------------------------------------------------
    |
    | Configure cursor-based pagination for better performance on large datasets
    |
    */

    'pagination' => [
        'driver' => 'cursor', // cursor or offset
        'per_page_default' => 20,
        'per_page_max' => 100,
        'cursor_parameter' => 'cursor',
        'per_page_parameter' => 'per_page',
        'order_by_parameter' => 'order_by',
        'direction_parameter' => 'direction',
        'allowed_order_by_fields' => [
            'id', 'created_at', 'updated_at', 'title', 'price', 'rating',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ETag Support
    |--------------------------------------------------------------------------
    |
    | Configure ETag support for conditional requests and client-side caching
    |
    */

    'etag' => [
        'enabled' => env('API_ETAG_ENABLED', true),
        'algorithm' => 'sha256', // sha256 or md5
        'cache_control' => 'public, max-age=3600', // Cache for 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    |
    | Configure API versioning strategy
    |
    */

    'versioning' => [
        'enabled' => true,
        'driver' => 'uri', // uri or header
        'uri_prefix' => '/api/v',
        'header' => 'X-API-Version',
        'default_version' => '1',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    |
    | Configure API documentation and Swagger/OpenAPI
    |
    */

    'documentation' => [
        'enabled' => env('API_DOCUMENTATION_ENABLED', true),
        'url' => env('API_DOCS_URL', '/api/docs'),
        'title' => 'PageTurner API',
        'description' => 'RESTful API for the PageTurner online bookstore',
        'version' => env('API_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CORS headers for API
    |
    */

    'cors' => [
        'enabled' => env('API_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('API_CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Version', 'If-None-Match'],
        'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset', 'ETag'],
        'max_age' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | Configure API authentication method
    |
    */

    'authentication' => [
        'method' => 'sanctum', // sanctum, jwt, basic, oauth2
        'sanctum' => [
            'stateful' => ['localhost', 'localhost:3000', 'localhost:5173'],
            'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 525600), // 1 year in minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Whitelisting
    |--------------------------------------------------------------------------
    |
    | IPs and users to exclude from rate limiting
    |
    */

    'rate_limit_whitelist' => [
        'ips' => explode(',', env('API_RATE_LIMIT_WHITELIST_IPS', '127.0.0.1,::1')),
        'user_ids' => explode(',', env('API_RATE_LIMIT_WHITELIST_USERS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API request/response logging
    |
    */

    'logging' => [
        'enabled' => env('API_LOGGING_ENABLED', true),
        'log_requests' => env('API_LOG_REQUESTS', false),
        'log_responses' => env('API_LOG_RESPONSES', false),
        'log_slow_queries' => env('API_LOG_SLOW_QUERIES', true),
        'slow_query_threshold_ms' => 500,
    ],
];
