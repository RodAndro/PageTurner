<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connection Pooling Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration manages database connection pooling for high-concurrency
    | scenarios with Swoole, RoadRunner, and other application servers.
    |
    */

    'enabled' => env('DB_POOLING_ENABLED', true),

    'pooling' => [
        'mysql' => [
            'enabled' => env('DB_MYSQL_POOLING_ENABLED', true),
            'min_connections' => env('DB_MYSQL_POOL_MIN', 5),
            'max_connections' => env('DB_MYSQL_POOL_MAX', 20),
            'idle_timeout' => env('DB_MYSQL_POOL_IDLE_TIMEOUT', 60), // seconds
            'max_wait_time' => env('DB_MYSQL_POOL_MAX_WAIT', 5), // seconds
            'retry_attempts' => env('DB_MYSQL_POOL_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('DB_MYSQL_POOL_RETRY_DELAY', 100), // milliseconds
        ],
        
        'postgresql' => [
            'enabled' => env('DB_PGSQL_POOLING_ENABLED', false),
            'min_connections' => env('DB_PGSQL_POOL_MIN', 3),
            'max_connections' => env('DB_PGSQL_POOL_MAX', 15),
            'idle_timeout' => env('DB_PGSQL_POOL_IDLE_TIMEOUT', 30),
            'max_wait_time' => env('DB_PGSQL_POOL_MAX_WAIT', 3),
            'retry_attempts' => env('DB_PGSQL_POOL_RETRY_ATTEMPTS', 2),
            'retry_delay' => env('DB_PGSQL_POOL_RETRY_DELAY', 200),
        ],
    ],

    'persistent_connections' => [
        'enabled' => env('DB_PERSISTENT_ENABLED', true),
        'connections' => [
            'read' => [
                'host' => env('DB_READ_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('DB_READ_PORT', env('DB_PORT', '3306')),
                'database' => env('DB_READ_DATABASE', env('DB_DATABASE', 'pageturner')),
                'username' => env('DB_READ_USERNAME', env('DB_USERNAME', 'root')),
                'password' => env('DB_READ_PASSWORD', env('DB_PASSWORD', '')),
                'charset' => env('DB_READ_CHARSET', 'utf8mb4'),
                'collation' => env('DB_READ_COLLATION', 'utf8mb4_unicode_ci'),
                'options' => [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES => true,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=STRICT_TRANS_TABLES',
                ],
            ],
            'write' => [
                'host' => env('DB_WRITE_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('DB_WRITE_PORT', env('DB_PORT', '3306')),
                'database' => env('DB_WRITE_DATABASE', env('DB_DATABASE', 'pageturner')),
                'username' => env('DB_WRITE_USERNAME', env('DB_USERNAME', 'root')),
                'password' => env('DB_WRITE_PASSWORD', env('DB_PASSWORD', '')),
                'charset' => env('DB_WRITE_CHARSET', 'utf8mb4'),
                'collation' => env('DB_WRITE_COLLATION', 'utf8mb4_unicode_ci'),
                'options' => [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES => true,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Better for writes
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=STRICT_TRANS_TABLES',
                ],
            ],
            'admin' => [
                'host' => env('DB_ADMIN_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('DB_ADMIN_PORT', env('DB_PORT', '3306')),
                'database' => env('DB_ADMIN_DATABASE', env('DB_DATABASE', 'pageturner')),
                'username' => env('DB_ADMIN_USERNAME', env('DB_USERNAME', 'root')),
                'password' => env('DB_ADMIN_PASSWORD', env('DB_PASSWORD', '')),
                'charset' => env('DB_ADMIN_CHARSET', 'utf8mb4'),
                'collation' => env('DB_ADMIN_COLLATION', 'utf8mb4_unicode_ci'),
                'options' => [
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES => true,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=STRICT_TRANS_TABLES',
                ],
            ],
        ],
    ],

    'connection_health' => [
        'enabled' => env('DB_HEALTH_CHECK_ENABLED', true),
        'check_interval' => env('DB_HEALTH_CHECK_INTERVAL', 30), // seconds
        'timeout' => env('DB_HEALTH_CHECK_TIMEOUT', 5), // seconds
        'max_failures' => env('DB_HEALTH_MAX_FAILURES', 3),
        'recovery_delay' => env('DB_HEALTH_RECOVERY_DELAY', 60), // seconds
        'metrics' => [
            'enabled' => env('DB_HEALTH_METRICS_ENABLED', true),
            'collect_interval' => env('DB_HEALTH_METRICS_INTERVAL', 60), // seconds
            'retention_hours' => env('DB_HEALTH_METRICS_RETENTION', 24),
        ],
    ],

    'load_balancing' => [
        'enabled' => env('DB_LOAD_BALANCING_ENABLED', false),
        'strategy' => env('DB_LOAD_BALANCING_STRATEGY', 'round_robin'), // round_robin, least_connections, weighted
        'weights' => [
            'read' => env('DB_READ_WEIGHT', 1),
            'write' => env('DB_WRITE_WEIGHT', 2),
            'admin' => env('DB_ADMIN_WEIGHT', 3),
        ],
        'health_check_before_routing' => env('DB_HEALTH_CHECK_BEFORE_ROUTING', true),
    ],

    'performance' => [
        'query_cache' => [
            'enabled' => env('DB_QUERY_CACHE_ENABLED', true),
            'size' => env('DB_QUERY_CACHE_SIZE', '64M'),
            'type' => env('DB_QUERY_CACHE_TYPE', '1'), // 0 = OFF, 1 = ON, 2 = DEMAND
        ],
        'buffer_pool' => [
            'enabled' => env('DB_BUFFER_POOL_ENABLED', true),
            'size' => env('DB_BUFFER_POOL_SIZE', '128M'),
        ],
        'connection_timeout' => [
            'read_timeout' => env('DB_READ_TIMEOUT', 30), // seconds
            'write_timeout' => env('DB_WRITE_TIMEOUT', 60), // seconds
            'connect_timeout' => env('DB_CONNECT_TIMEOUT', 5), // seconds
        ],
        'slow_query_log' => [
            'enabled' => env('DB_SLOW_QUERY_LOG_ENABLED', true),
            'threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
            'log_file' => env('DB_SLOW_QUERY_LOG_FILE', storage_path('logs/mysql-slow.log')),
        ],
    ],

    'monitoring' => [
        'enabled' => env('DB_MONITORING_ENABLED', true),
        'metrics' => [
            'connections' => [
                'enabled' => env('DB_MONITOR_CONNECTIONS', true),
                'collect_interval' => env('DB_MONITOR_CONNECTIONS_INTERVAL', 10), // seconds
            ],
            'queries' => [
                'enabled' => env('DB_MONITOR_QUERIES', true),
                'collect_interval' => env('DB_MONITOR_QUERIES_INTERVAL', 5), // seconds
                'slow_threshold' => env('DB_MONITOR_QUERIES_SLOW_THRESHOLD', 100), // milliseconds
            ],
            'performance' => [
                'enabled' => env('DB_MONITOR_PERFORMANCE', true),
                'collect_interval' => env('DB_MONITOR_PERFORMANCE_INTERVAL', 60), // seconds
                'metrics' => ['cpu', 'memory', 'disk_io', 'network'],
            ],
        ],
        'alerts' => [
            'enabled' => env('DB_MONITOR_ALERTS', true),
            'channels' => [
                'email' => env('DB_MONITOR_EMAIL_ALERTS', true),
                'slack' => env('DB_MONITOR_SLACK_ALERTS', false),
                'webhook' => env('DB_MONITOR_WEBHOOK_ALERTS', false),
            ],
            'thresholds' => [
                'connection_pool_usage' => env('DB_ALERT_CONNECTION_POOL_THRESHOLD', 80), // percentage
                'slow_queries_per_minute' => env('DB_ALERT_SLOW_QUERIES_THRESHOLD', 10),
                'connection_failures_per_minute' => env('DB_ALERT_CONNECTION_FAILURES_THRESHOLD', 5),
                'memory_usage_percentage' => env('DB_ALERT_MEMORY_THRESHOLD', 85), // percentage
            ],
        ],
    ],

    'sharding' => [
        'enabled' => env('DB_SHARDING_ENABLED', false),
        'strategy' => env('DB_SHARDING_STRATEGY', 'modulo'), // modulo, range, hash
        'shard_count' => env('DB_SHARD_COUNT', 4),
        'shard_key' => env('DB_SHARD_KEY', 'id'), // id, user_id, created_at
        'auto_rebalancing' => env('DB_SHARD_AUTO_REBALANCING', false),
        'rebalance_threshold' => env('DB_SHARD_REBALANCE_THRESHOLD', 20), // percentage
        'migrations' => [
            'auto_create' => env('DB_SHARD_AUTO_MIGRATIONS', true),
            'migration_batch_size' => env('DB_SHARD_MIGRATION_BATCH_SIZE', 10000),
        ],
    ],

    'testing' => [
        'enabled' => env('DB_TESTING_ENABLED', false),
        'mock_failures' => env('DB_TEST_MOCK_FAILURES', false),
        'failure_rate' => env('DB_TEST_FAILURE_RATE', 0.05), // 5% failure rate
        'connection_pool_simulation' => env('DB_TEST_POOL_SIMULATION', false),
    ],
];
