<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Excel Import/Export Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for Excel import/export operations
    | including chunking, memory management, and validation settings.
    |
    */

    'exports' => [
        /*
        |--------------------------------------------------------------------------
        | Export Chunking Configuration
        |--------------------------------------------------------------------------
        |
        | Configure how large datasets are chunked for memory-efficient exports.
        |
        */
        'chunk_size' => env('EXCEL_EXPORT_CHUNK_SIZE', 1000),
        'max_memory_mb' => env('EXCEL_EXPORT_MAX_MEMORY_MB', 256),
        'timeout_seconds' => env('EXCEL_EXPORT_TIMEOUT', 300),

        /*
        |--------------------------------------------------------------------------
        | Export Formats
        |--------------------------------------------------------------------------
        |
        | Configure supported export formats and their settings.
        |
        */
        'formats' => [
            'xlsx' => [
                'enabled' => true,
                'extension' => '.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max_rows' => 1048576, // Excel limit
                'compression' => true,
            ],
            'csv' => [
                'enabled' => true,
                'extension' => '.csv',
                'mime_type' => 'text/csv',
                'max_rows' => 1000000, // Practical limit
                'delimiter' => ',',
                'enclosure' => '"',
                'line_ending' => "\n",
                'include_bom' => true,
            ],
            'pdf' => [
                'enabled' => true,
                'extension' => '.pdf',
                'mime_type' => 'application/pdf',
                'max_rows' => 10000, // Practical limit for PDF
                'orientation' => 'landscape',
                'paper_size' => 'A4',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Export Storage
        |--------------------------------------------------------------------------
        |
        | Configure where export files are stored.
        |
        */
        'storage' => [
            'disk' => env('EXCEL_EXPORT_DISK', 'local'),
            'path' => env('EXCEL_EXPORT_PATH', 'exports'),
            'temporary_path' => env('EXCEL_TEMP_PATH', 'temp/exports'),
            'cleanup_hours' => env('EXCEL_CLEANUP_HOURS', 24),
        ],

        /*
        |--------------------------------------------------------------------------
        | Export Queue Configuration
        |--------------------------------------------------------------------------
        |
        | Configure queue settings for large export operations.
        |
        */
        'queue' => [
            'enabled' => env('EXCEL_QUEUE_ENABLED', true),
            'connection' => env('EXCEL_QUEUE_CONNECTION', 'default'),
            'name' => 'excel-exports',
            'delay_seconds' => env('EXCEL_QUEUE_DELAY', 0),
            'tries' => env('EXCEL_QUEUE_TRIES', 3),
            'backoff_seconds' => [60, 300, 900], // 1min, 5min, 15min
        ],

        /*
        |--------------------------------------------------------------------------
        | Export Caching
        |--------------------------------------------------------------------------
        |
        | Configure caching for repeated export requests.
        |
        */
        'cache' => [
            'enabled' => env('EXCEL_CACHE_ENABLED', false),
            'ttl_minutes' => env('EXCEL_CACHE_TTL', 60),
            'key_prefix' => 'excel_export:',
            'max_size_mb' => env('EXCEL_CACHE_MAX_SIZE_MB', 100),
        ],

        /*
        |--------------------------------------------------------------------------
        | Export Validation
        |--------------------------------------------------------------------------
        |
        | Configure validation rules for export requests.
        |
        */
        'validation' => [
            'max_records' => env('EXCEL_EXPORT_MAX_RECORDS', 100000),
            'allowed_filters' => ['date_range', 'category', 'status', 'price_range'],
            'required_permissions' => ['export:data'],
            'rate_limit_per_hour' => env('EXCEL_EXPORT_RATE_LIMIT', 10),
        ],
    ],

    'imports' => [
        /*
        |--------------------------------------------------------------------------
        | Import Chunking Configuration
        |--------------------------------------------------------------------------
        |
        | Configure how large datasets are chunked for memory-efficient imports.
        |
        */
        'chunk_size' => env('EXCEL_IMPORT_CHUNK_SIZE', 1000),
        'max_memory_mb' => env('EXCEL_IMPORT_MAX_MEMORY_MB', 256),
        'timeout_seconds' => env('EXCEL_IMPORT_TIMEOUT', 600), // 10 minutes

        /*
        |--------------------------------------------------------------------------
        | Import Validation
        |--------------------------------------------------------------------------
        |
        | Configure validation rules for import operations.
        |
        */
        'validation' => [
            'strict_mode' => env('EXCEL_IMPORT_STRICT_MODE', true),
            'skip_invalid_rows' => env('EXCEL_IMPORT_SKIP_INVALID', false),
            'max_errors' => env('EXCEL_IMPORT_MAX_ERRORS', 100),
            'required_columns' => ['title', 'author', 'isbn', 'price'],
            'optional_columns' => ['description', 'category', 'publication_date'],
            'column_mappings' => [
                // Map common variations to standard column names
                'book_title' => 'title',
                'book_name' => 'title',
                'author_name' => 'author',
                'price_usd' => 'price',
                'category_name' => 'category',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Import Data Processing
        |--------------------------------------------------------------------------
        |
        | Configure how import data is processed and transformed.
        |
        */
        'processing' => [
            'trim_strings' => env('EXCEL_IMPORT_TRIM_STRINGS', true),
            'convert_empty_to_null' => env('EXCEL_IMPORT_EMPTY_TO_NULL', true),
            'normalize_case' => env('EXCEL_IMPORT_NORMALIZE_CASE', 'title'), // title, upper, lower, none
            'date_format' => env('EXCEL_IMPORT_DATE_FORMAT', 'Y-m-d'),
            'decimal_separator' => env('EXCEL_IMPORT_DECIMAL_SEPARATOR', '.'),
            'thousands_separator' => env('EXCEL_IMPORT_THOUSANDS_SEPARATOR', ','),
        ],

        /*
        |--------------------------------------------------------------------------
        | Import Queue Configuration
        |--------------------------------------------------------------------------
        |
        | Configure queue settings for large import operations.
        |
        */
        'queue' => [
            'enabled' => env('EXCEL_IMPORT_QUEUE_ENABLED', true),
            'connection' => env('EXCEL_IMPORT_QUEUE_CONNECTION', 'default'),
            'name' => 'excel-imports',
            'delay_seconds' => env('EXCEL_IMPORT_QUEUE_DELAY', 0),
            'tries' => env('EXCEL_IMPORT_QUEUE_TRIES', 3),
            'backoff_seconds' => [60, 300, 900], // 1min, 5min, 15min
            'batch_size' => env('EXCEL_IMPORT_BATCH_SIZE', 500),
        ],

        /*
        |--------------------------------------------------------------------------
        | Import Storage
        |--------------------------------------------------------------------------
        |
        | Configure where import files are stored.
        |
        */
        'storage' => [
            'disk' => env('EXCEL_IMPORT_DISK', 'local'),
            'path' => env('EXCEL_IMPORT_PATH', 'imports'),
            'temporary_path' => env('EXCEL_IMPORT_TEMP_PATH', 'temp/imports'),
            'cleanup_hours' => env('EXCEL_IMPORT_CLEANUP_HOURS', 48),
            'max_file_size_mb' => env('EXCEL_IMPORT_MAX_FILE_SIZE_MB', 50),
        ],

        /*
        |--------------------------------------------------------------------------
        | Import Monitoring
        |--------------------------------------------------------------------------
        |
        | Configure monitoring and logging for import operations.
        |
        */
        'monitoring' => [
            'log_all_operations' => env('EXCEL_IMPORT_LOG_ALL', true),
            'log_memory_usage' => env('EXCEL_IMPORT_LOG_MEMORY', true),
            'log_processing_time' => env('EXCEL_IMPORT_LOG_TIME', true),
            'alert_on_errors' => env('EXCEL_IMPORT_ALERT_ERRORS', true),
            'alert_threshold_percent' => env('EXCEL_IMPORT_ERROR_THRESHOLD', 5), // 5% error rate
        ],

        /*
        |--------------------------------------------------------------------------
        | Import Rollback
        |--------------------------------------------------------------------------
        |
        | Configure rollback settings for failed imports.
        |
        */
        'rollback' => [
            'enabled' => env('EXCEL_IMPORT_ROLLBACK_ENABLED', true),
            'auto_rollback_on_failure' => env('EXCEL_IMPORT_AUTO_ROLLBACK', false),
            'keep_rollback_logs' => env('EXCEL_IMPORT_KEEP_ROLLBACK_LOGS', true),
            'rollback_retention_days' => env('EXCEL_IMPORT_ROLLBACK_RETENTION', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Management
    |--------------------------------------------------------------------------
    |
    | Global memory management settings for Excel operations.
    |
    */
    'memory' => [
        'monitoring' => [
            'enabled' => env('EXCEL_MEMORY_MONITORING', true),
            'alert_threshold_percent' => env('EXCEL_MEMORY_ALERT_THRESHOLD', 80),
            'log_snapshots' => env('EXCEL_MEMORY_LOG_SNAPSHOTS', true),
            'snapshot_interval' => env('EXCEL_MEMORY_SNAPSHOT_INTERVAL', 100), // Every 100 rows
        ],
        'optimization' => [
            'garbage_collection' => env('EXCEL_GC_ENABLED', true),
            'gc_frequency' => env('EXCEL_GC_FREQUENCY', 1000), // Every 1000 operations
            'clear_cache' => env('EXCEL_CLEAR_CACHE', true),
            'cache_clear_frequency' => env('EXCEL_CACHE_CLEAR_FREQUENCY', 5000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for Excel operations.
    |
    */
    'security' => [
        'file_validation' => [
            'enabled' => env('EXCEL_FILE_VALIDATION', true),
            'allowed_mime_types' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv',
                'application/csv',
            ],
            'max_file_size_mb' => env('EXCEL_MAX_FILE_SIZE_MB', 50),
            'scan_for_macros' => env('EXCEL_SCAN_MACROS', true),
        ],
        'data_sanitization' => [
            'enabled' => env('EXCEL_SANITIZE_DATA', true),
            'remove_html_tags' => env('EXCEL_REMOVE_HTML', true),
            'escape_special_chars' => env('EXCEL_ESCAPE_SPECIAL', false),
            'validate_urls' => env('EXCEL_VALIDATE_URLS', true),
        ],
        'access_control' => [
            'require_authentication' => env('EXCEL_REQUIRE_AUTH', true),
            'required_permissions' => [
                'import' => ['import:data'],
                'export' => ['export:data'],
            ],
            'ip_whitelist' => env('EXCEL_IP_WHITELIST', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance optimization settings.
    |
    */
    'performance' => [
        'caching' => [
            'enabled' => env('EXCEL_PERFORMANCE_CACHE', true),
            'driver' => env('EXCEL_CACHE_DRIVER', 'redis'),
            'ttl_seconds' => env('EXCEL_PERFORMANCE_CACHE_TTL', 3600),
            'max_entries' => env('EXCEL_PERFORMANCE_CACHE_MAX', 10000),
        ],
        'database' => [
            'use_transactions' => env('EXCEL_USE_TRANSACTIONS', true),
            'batch_inserts' => env('EXCEL_BATCH_INSERTS', true),
            'batch_size' => env('EXCEL_BATCH_SIZE', 500),
            'disable_query_log' => env('EXCEL_DISABLE_QUERY_LOG', true),
        ],
        'processing' => [
            'parallel_processing' => env('EXCEL_PARALLEL_PROCESSING', false),
            'max_workers' => env('EXCEL_MAX_WORKERS', 4),
            'chunk_overlap' => env('EXCEL_CHUNK_OVERLAP', 50), // Overlap chunks for safety
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing environments.
    |
    */
    'testing' => [
        'enabled' => env('EXCEL_TESTING_MODE', false),
        'mock_file_system' => env('EXCEL_MOCK_FS', false),
        'simulate_large_files' => env('EXCEL_SIMULATE_LARGE', false),
        'test_data_size' => env('EXCEL_TEST_DATA_SIZE', 1000),
        'force_failures' => env('EXCEL_FORCE_FAILURES', false),
        'failure_rate' => env('EXCEL_FAILURE_RATE', 0.1), // 10% failure rate
    ],
];
