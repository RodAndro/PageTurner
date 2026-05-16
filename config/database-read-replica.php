<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Read Replica Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration manages read replica connections for offloading
    | read-intensive operations like reporting and analytics.
    |
    */

    'read_replicas' => [
        'enable' => env('DB_READ_REPLICAS_ENABLED', true),
        'default_connection' => env('DB_READ_REPLICA_CONNECTION', 'read_replica'),
        
        'connections' => [
            'read_replica' => [
                'driver' => env('DB_READ_REPLICA_DRIVER', 'mysql'),
                'host' => env('DB_READ_REPLICA_HOST', 'localhost'),
                'port' => env('DB_READ_REPLICA_PORT', '3306'),
                'database' => env('DB_READ_REPLICA_DATABASE', 'pageturner_read'),
                'username' => env('DB_READ_REPLICA_USERNAME', 'replica_user'),
                'password' => env('DB_READ_REPLICA_PASSWORD', ''),
                'charset' => env('DB_READ_REPLICA_CHARSET', 'utf8mb4'),
                'collation' => env('DB_READ_REPLICA_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix' => env('DB_READ_REPLICA_PREFIX', ''),
                'strict' => env('DB_READ_REPLICA_STRICT', true),
                'engine' => env('DB_READ_REPLICA_ENGINE', null),
                
                // Read replica specific settings
                'replication_lag' => env('DB_READ_REPLICA_LAG_TOLERANCE', 5), // seconds
                'health_check_interval' => env('DB_READ_REPLICA_HEALTH_CHECK', 30), // seconds
                'failover_enabled' => env('DB_READ_REPLICA_FAILOVER_ENABLED', true),
                'connection_timeout' => env('DB_READ_REPLICA_TIMEOUT', 5),
                'read_timeout' => env('DB_READ_REPLICA_READ_TIMEOUT', 30),
                
                // Connection pool settings
                'pool' => [
                    'min_connections' => env('DB_READ_REPLICA_POOL_MIN', 5),
                    'max_connections' => env('DB_READ_REPLICA_POOL_MAX', 20),
                    'idle_timeout' => env('DB_READ_REPLICA_POOL_IDLE', 60),
                    'max_wait_time' => env('DB_READ_REPLICA_POOL_WAIT', 5),
                ],
            ],
            
            'read_replica_2' => [
                'driver' => env('DB_READ_REPLICA_2_DRIVER', 'mysql'),
                'host' => env('DB_READ_REPLICA_2_HOST', 'localhost'),
                'port' => env('DB_READ_REPLICA_2_PORT', '3307'),
                'database' => env('DB_READ_REPLICA_2_DATABASE', 'pageturner_read_2'),
                'username' => env('DB_READ_REPLICA_2_USERNAME', 'replica_user'),
                'password' => env('DB_READ_REPLICA_2_PASSWORD', ''),
                'charset' => env('DB_READ_REPLICA_2_CHARSET', 'utf8mb4'),
                'collation' => env('DB_READ_REPLICA_2_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix' => env('DB_READ_REPLICA_2_PREFIX', ''),
                'strict' => env('DB_READ_REPLICA_2_STRICT', true),
                'engine' => env('DB_READ_REPLICA_2_ENGINE', null),
                
                'replication_lag' => env('DB_READ_REPLICA_2_LAG_TOLERANCE', 5),
                'health_check_interval' => env('DB_READ_REPLICA_2_HEALTH_CHECK', 30),
                'failover_enabled' => env('DB_READ_REPLICA_2_FAILOVER_ENABLED', true),
                'connection_timeout' => env('DB_READ_REPLICA_2_TIMEOUT', 5),
                'read_timeout' => env('DB_READ_REPLICA_2_READ_TIMEOUT', 30),
                
                'pool' => [
                    'min_connections' => env('DB_READ_REPLICA_2_POOL_MIN', 3),
                    'max_connections' => env('DB_READ_REPLICA_2_POOL_MAX', 15),
                    'idle_timeout' => env('DB_READ_REPLICA_2_POOL_IDLE', 60),
                    'max_wait_time' => env('DB_READ_REPLICA_2_POOL_WAIT', 5),
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Routing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which queries should be routed to read replicas
    | and which should use the primary write connection.
    |
    */

    'query_routing' => [
        'auto_route_reads' => env('DB_AUTO_ROUTE_READS', true),
        'route_selects' => env('DB_ROUTE_SELECTS', true),
        'route_aggregates' => env('DB_ROUTE_AGGREGATES', false),
        'route_joins' => env('DB_ROUTE_JOINS', true),
        'route_complex_queries' => env('DB_ROUTE_COMPLEX', true),
        
        // Query complexity thresholds
        'complexity_thresholds' => [
            'join_count' => env('DB_COMPLEX_JOIN_THRESHOLD', 3),
            'where_conditions' => env('DB_COMPLEX_WHERE_THRESHOLD', 5),
            'execution_time_estimate' => env('DB_COMPLEX_TIME_THRESHOLD', 100), // ms
        ],
        
        // Force specific queries to use replicas
        'force_replica_queries' => [
            'reporting.*',
            'analytics.*',
            'dashboard.*',
            'search.*',
            'export.*',
            'bestseller.*',
            'inventory.*',
            'order_history.*',
        ],
        
        // Force specific queries to use primary
        'force_primary_queries' => [
            'orders.*',
            'users.*',
            'categories.*',
            'books.*', // For write operations
            'reviews.*', // For write operations
            'import.*',
            'backup.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure health checks and monitoring for read replicas
    |
    */

    'health_monitoring' => [
        'enabled' => env('DB_REPLICA_HEALTH_ENABLED', true),
        'check_interval' => env('DB_REPLICA_HEALTH_INTERVAL', 30), // seconds
        'timeout' => env('DB_REPLICA_HEALTH_TIMEOUT', 5), // seconds
        'retry_attempts' => env('DB_REPLICA_HEALTH_RETRIES', 3),
        'backoff_seconds' => [1, 2, 4], // Exponential backoff
        
        'metrics' => [
            'replication_lag' => true,
            'connection_pool_status' => true,
            'query_performance' => true,
            'error_rates' => true,
        ],
        
        'alerts' => [
            'lag_threshold' => env('DB_REPLICA_LAG_ALERT', 10), // seconds
            'connection_threshold' => env('DB_REPLICA_CONNECTION_ALERT', 80), // percentage
            'error_rate_threshold' => env('DB_REPLICA_ERROR_ALERT', 5), // percentage
            
            'notification_channels' => [
                'email' => env('DB_REPLICA_EMAIL_ALERTS', true),
                'slack' => env('DB_REPLICA_SLACK_ALERTS', false),
                'webhook' => env('DB_REPLICA_WEBHOOK_ALERTS', false),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failover Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic failover behavior when replicas fail
    |
    */

    'failover' => [
        'enabled' => env('DB_REPLICA_FAILOVER_ENABLED', true),
        'health_check_interval' => env('DB_REPLICA_FAILOVER_CHECK', 10), // seconds
        'max_failures' => env('DB_REPLICA_MAX_FAILURES', 3),
        'failure_window' => env('DB_REPLICA_FAILURE_WINDOW', 60), // seconds
        
        'strategy' => env('DB_REPLICA_FAILOVER_STRATEGY', 'round_robin'), // round_robin, weighted, priority
        
        'weights' => [
            'read_replica' => env('DB_REPLICA_WEIGHT', 1),
            'read_replica_2' => env('DB_REPLICA_2_WEIGHT', 1),
        ],
        
        'priority_order' => [
            'read_replica',
            'read_replica_2',
        ],
        
        'recovery' => [
            'auto_recovery' => env('DB_REPLICA_AUTO_RECOVERY', true),
            'recovery_interval' => env('DB_REPLICA_RECOVERY_INTERVAL', 300), // seconds
            'health_check_before_recovery' => env('DB_REPLICA_RECOVERY_HEALTH_CHECK', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Configuration for optimizing read replica performance
    |
    */

    'performance' => [
        'query_cache' => [
            'enabled' => env('DB_REPLICA_QUERY_CACHE', true),
            'ttl' => env('DB_REPLICA_CACHE_TTL', 3600), // seconds
            'max_size' => env('DB_REPLICA_CACHE_MAX_SIZE', 1000), // entries
            'cache_prefix' => env('DB_REPLICA_CACHE_PREFIX', 'replica_cache:'),
        ],
        
        'connection_pooling' => [
            'persistent_connections' => env('DB_REPLICA_PERSISTENT', true),
            'connection_timeout' => env('DB_REPLICA_CONN_TIMEOUT', 5),
            'read_timeout' => env('DB_REPLICA_READ_TIMEOUT', 30),
            'idle_timeout' => env('DB_REPLICA_IDLE_TIMEOUT', 60),
            'retry_attempts' => env('DB_REPLICA_RETRY_ATTEMPTS', 3),
        ],
        
        'query_optimization' => [
            'force_index_hints' => env('DB_REPLICA_FORCE_INDEXES', false),
            'use_covering_indexes' => env('DB_REPLICA_COVERING_INDEXES', true),
            'optimize_joins' => env('DB_REPLICA_OPTIMIZE_JOINS', true),
            'batch_size' => env('DB_REPLICA_BATCH_SIZE', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Debugging
    |--------------------------------------------------------------------------
    |
    | Configure logging for read replica operations
    |
    */

    'logging' => [
        'enabled' => env('DB_REPLICA_LOGGING', true),
        'log_queries' => env('DB_REPLICA_LOG_QUERIES', false),
        'log_slow_queries' => env('DB_REPLICA_LOG_SLOW', true),
        'slow_query_threshold' => env('DB_REPLICA_SLOW_THRESHOLD', 1000), // milliseconds
        'log_connection_errors' => env('DB_REPLICA_LOG_CONNECTIONS', true),
        'log_failover_events' => env('DB_REPLICA_LOG_FAILOVER', true),
        'log_health_checks' => env('DB_REPLICA_LOG_HEALTH', true),
        
        'channels' => [
            'default' => 'read-replica',
            'slow_queries' => 'read-replica-slow',
            'failover' => 'read-replica-failover',
            'health' => 'read-replica-health',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing read replica functionality
    |
    */

    'testing' => [
        'enabled' => env('DB_REPLICA_TESTING', false),
        'mock_replica_lag' => env('DB_REPLICA_MOCK_LAG', false),
        'mock_failures' => env('DB_REPLICA_MOCK_FAILURES', false),
        'failure_rate' => env('DB_REPLICA_FAILURE_RATE', 0.01), // 1% failure rate
        'lag_simulation' => env('DB_REPLICA_LAG_SIMULATION', 0), // seconds
        'connection_pool_simulation' => env('DB_REPLICA_POOL_SIM', false),
    ],
];
