<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for audit logging including
    | what to log, how to store it, and security settings.
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Audit Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where audit logs are stored and how they're managed.
    |
    */

    'storage' => [
        'driver' => env('AUDIT_DRIVER', 'database'),
        'table' => env('AUDIT_TABLE', 'audit_logs'),
        'connection' => env('AUDIT_CONNECTION', null),
        'chunk_size' => env('AUDIT_CHUNK_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which events should be audited.
    |
    */

    'events' => [
        'enabled' => env('AUDIT_EVENTS_ENABLED', true),
        'log_all' => env('AUDIT_LOG_ALL', false),
        'include' => [
            'created' => true,
            'updated' => true,
            'deleted' => true,
            'restored' => true,
            'login' => true,
            'logout' => true,
            'failed_login' => true,
            'password_changed' => true,
            'password_reset' => true,
            'role_changed' => true,
            'permission_changed' => true,
            'export_downloaded' => true,
            'import_uploaded' => true,
            'backup_created' => true,
            'backup_restored' => true,
        ],
        'exclude' => [
            // Events to exclude from auditing
            'heartbeat' => true,
            'page_view' => false, // Set to true to exclude page views
            'api_health_check' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Models Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which models should be audited.
    |
    */

    'models' => [
        'enabled' => env('AUDIT_MODELS_ENABLED', true),
        'audit_all' => env('AUDIT_ALL_MODELS', false),
        'include' => [
            'App\Models\User' => [
                'enabled' => true,
                'events' => ['created', 'updated', 'deleted'],
                'exclude_fields' => ['password', 'remember_token'],
                'sensitive_fields' => ['email', 'phone', 'address'],
            ],
            'App\Models\Book' => [
                'enabled' => true,
                'events' => ['created', 'updated', 'deleted'],
                'exclude_fields' => [],
                'sensitive_fields' => [],
            ],
            'App\Models\Order' => [
                'enabled' => true,
                'events' => ['created', 'updated', 'deleted'],
                'exclude_fields' => ['credit_card_number', 'cvv'],
                'sensitive_fields' => ['billing_address', 'shipping_address'],
            ],
            'App\Models\Category' => [
                'enabled' => true,
                'events' => ['created', 'updated', 'deleted'],
                'exclude_fields' => [],
                'sensitive_fields' => [],
            ],
            'App\Models\Review' => [
                'enabled' => true,
                'events' => ['created', 'updated', 'deleted'],
                'exclude_fields' => [],
                'sensitive_fields' => ['ip_address'],
            ],
        ],
        'exclude' => [
            // Models to exclude from auditing
            'App\Models\TempFile' => true,
            'App\Models\CacheEntry' => true,
            'App\Models\Session' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Redaction Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how sensitive data is handled in audit logs.
    |
    */

    'redaction' => [
        'enabled' => env('AUDIT_REDACTION_ENABLED', true),
        'method' => env('AUDIT_REDACTION_METHOD', 'mask'), // mask, hash, remove
        'mask_char' => env('AUDIT_MASK_CHAR', '*'),
        'mask_length' => env('AUDIT_MASK_LENGTH', 4),
        'hash_algorithm' => env('AUDIT_HASH_ALGORITHM', 'sha256'),
        'hash_salt' => env('AUDIT_HASH_SALT', 'audit_salt_key'),
        'sensitive_patterns' => [
            // Regex patterns to identify sensitive data
            '/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/' => 'credit_card', // Credit card
            '/\b\d{3}-\d{2}-\d{4}\b/' => 'ssn', // Social Security Number
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => 'email', // Email
            '/\b\d{10,}\b/' => 'phone', // Phone number
        ],
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'credit_card_number',
            'cvv',
            'ssn',
            'social_security_number',
            'bank_account_number',
            'api_key',
            'secret_key',
            'access_token',
            'refresh_token',
            'pin',
            'security_answer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Retention Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how long audit logs are retained.
    |
    */

    'retention' => [
        'default_days' => env('AUDIT_RETENTION_DAYS', 365),
        'archive_days' => env('AUDIT_ARCHIVE_DAYS', 90),
        'cleanup_schedule' => env('AUDIT_CLEANUP_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
        'archive_before_delete' => env('AUDIT_ARCHIVE_BEFORE_DELETE', true),
        'archive_storage' => [
            'driver' => env('AUDIT_ARCHIVE_DRIVER', 'local'),
            'disk' => env('AUDIT_ARCHIVE_DISK', 'local'),
            'path' => env('AUDIT_ARCHIVE_PATH', 'audit-archive'),
        ],
        'compression' => [
            'enabled' => env('AUDIT_COMPRESSION_ENABLED', true),
            'algorithm' => env('AUDIT_COMPRESSION_ALGORITHM', 'gzip'),
            'level' => env('AUDIT_COMPRESSION_LEVEL', 6),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance settings for audit logging.
    |
    */

    'performance' => [
        'queue_enabled' => env('AUDIT_QUEUE_ENABLED', true),
        'queue_connection' => env('AUDIT_QUEUE_CONNECTION', 'default'),
        'queue_name' => env('AUDIT_QUEUE_NAME', 'audit-logs'),
        'batch_size' => env('AUDIT_BATCH_SIZE', 100),
        'async_processing' => env('AUDIT_ASYNC_PROCESSING', true),
        'memory_limit_mb' => env('AUDIT_MEMORY_LIMIT_MB', 256),
        'timeout_seconds' => env('AUDIT_TIMEOUT_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for audit logs.
    |
    */

    'security' => [
        'tamper_protection' => [
            'enabled' => env('AUDIT_TAMPER_PROTECTION_ENABLED', true),
            'algorithm' => env('AUDIT_CHECKSUM_ALGORITHM', 'sha256'),
            'include_timestamp' => true,
            'include_user_id' => true,
            'verify_on_read' => false,
        ],
        'encryption' => [
            'enabled' => env('AUDIT_ENCRYPTION_ENABLED', false),
            'algorithm' => env('AUDIT_ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
            'key' => env('AUDIT_ENCRYPTION_KEY'),
            'iv' => env('AUDIT_ENCRYPTION_IV'),
        ],
        'access_control' => [
            'require_authentication' => env('AUDIT_REQUIRE_AUTH', true),
            'required_permissions' => [
                'view' => ['audit:logs:view'],
                'export' => ['audit:logs:export'],
                'delete' => ['audit:logs:delete'],
            ],
            'ip_whitelist' => env('AUDIT_IP_WHITELIST', ''),
            'log_all_access' => env('AUDIT_LOG_ALL_ACCESS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Search and Filtering Configuration
    |--------------------------------------------------------------------------
    |
    | Configure search and filtering capabilities.
    |
    */

    'search' => [
        'enabled' => env('AUDIT_SEARCH_ENABLED', true),
        'index_fields' => [
            'user_id',
            'entity_type',
            'entity_id',
            'event',
            'created_at',
            'ip_address',
            'user_agent',
        ],
        'fulltext_fields' => [
            'old_values',
            'new_values',
            'description',
        ],
        'filters' => [
            'date_range' => true,
            'user_filter' => true,
            'entity_filter' => true,
            'event_filter' => true,
            'ip_filter' => true,
            'custom_filter' => true,
        ],
        'pagination' => [
            'default_per_page' => env('AUDIT_DEFAULT_PER_PAGE', 50),
            'max_per_page' => env('AUDIT_MAX_PER_PAGE', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure export settings for audit logs.
    |
    */

    'export' => [
        'enabled' => env('AUDIT_EXPORT_ENABLED', true),
        'formats' => [
            'csv' => [
                'enabled' => true,
                'delimiter' => ',',
                'enclosure' => '"',
                'include_headers' => true,
            ],
            'json' => [
                'enabled' => true,
                'pretty_print' => true,
                'include_metadata' => true,
            ],
            'xml' => [
                'enabled' => false,
                'root_element' => 'audit_logs',
                'item_element' => 'log',
            ],
            'pdf' => [
                'enabled' => false,
                'orientation' => 'landscape',
                'paper_size' => 'A4',
            ],
        ],
        'max_records' => env('AUDIT_EXPORT_MAX_RECORDS', 100000),
        'queue_large_exports' => env('AUDIT_QUEUE_LARGE_EXPORTS', true),
        'large_export_threshold' => env('AUDIT_LARGE_EXPORT_THRESHOLD', 10000),
        'storage' => [
            'disk' => env('AUDIT_EXPORT_DISK', 'local'),
            'path' => env('AUDIT_EXPORT_PATH', 'audit-exports'),
            'retention_hours' => env('AUDIT_EXPORT_RETENTION_HOURS', 24),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configure when and how audit notifications are sent.
    |
    */

    'notifications' => [
        'enabled' => env('AUDIT_NOTIFICATIONS_ENABLED', true),
        'channels' => [
            'email' => env('AUDIT_EMAIL_NOTIFICATIONS', true),
            'slack' => env('AUDIT_SLACK_NOTIFICATIONS', false),
            'webhook' => env('AUDIT_WEBHOOK_NOTIFICATIONS', false),
        ],
        'events' => [
            'critical_events' => [
                'deleted' => ['admin', 'security'],
                'role_changed' => ['admin', 'security'],
                'failed_login' => ['security'],
                'tampering_detected' => ['admin', 'security'],
            ],
            'important_events' => [
                'created' => ['admin'],
                'updated' => ['admin'],
                'password_changed' => ['admin'],
                'export_downloaded' => ['admin'],
            ],
            'info_events' => [
                'login' => ['admin'],
                'logout' => ['admin'],
                'import_uploaded' => ['admin'],
            ],
        ],
        'email' => [
            'to' => env('AUDIT_NOTIFICATION_EMAIL', 'admin@example.com'),
            'from' => env('AUDIT_NOTIFICATION_FROM', 'audit@example.com'),
            'subject_prefix' => '[Audit] ',
        ],
        'slack' => [
            'webhook_url' => env('AUDIT_SLACK_WEBHOOK_URL'),
            'channel' => env('AUDIT_SLACK_CHANNEL', '#audit'),
            'username' => env('AUDIT_SLACK_BOT_NAME', 'AuditBot'),
        ],
        'webhook' => [
            'url' => env('AUDIT_WEBHOOK_URL'),
            'secret' => env('AUDIT_WEBHOOK_SECRET'),
            'timeout' => 30,
            'retry_attempts' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure analytics and reporting for audit logs.
    |
    */

    'analytics' => [
        'enabled' => env('AUDIT_ANALYTICS_ENABLED', true),
        'aggregation_schedule' => env('AUDIT_AGGREGATION_SCHEDULE', '0 1 * * *'), // Daily at 1 AM
        'metrics' => [
            'daily_activity' => true,
            'user_activity' => true,
            'entity_changes' => true,
            'security_events' => true,
            'performance_metrics' => true,
        ],
        'reports' => [
            'daily_summary' => true,
            'weekly_trends' => true,
            'monthly_analytics' => true,
            'security_report' => true,
            'compliance_report' => true,
        ],
        'retention' => [
            'raw_logs_days' => env('AUDIT_RAW_RETENTION_DAYS', 365),
            'aggregated_data_days' => env('AUDIT_AGGREGATED_RETENTION_DAYS', 1095), // 3 years
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing audit functionality.
    |
    */

    'testing' => [
        'enabled' => env('AUDIT_TESTING', false),
        'mock_storage' => env('AUDIT_MOCK_STORAGE', false),
        'simulate_failures' => env('AUDIT_SIMULATE_FAILURES', false),
        'failure_rate' => env('AUDIT_FAILURE_RATE', 0.01), // 1% failure rate
        'test_data_size' => env('AUDIT_TEST_DATA_SIZE', 1000),
        'force_checksum_mismatch' => env('AUDIT_FORCE_CHECKSUM_MISMATCH', false),
    ],
];
