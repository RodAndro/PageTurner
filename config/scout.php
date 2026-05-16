<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scout Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for Laravel Scout full-text search
    | with database and Elasticsearch drivers for performance optimization.
    |
    */

    'driver' => env('SCOUT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for search indexing operations.
    |
    */

    'queue' => [
        'connection' => env('SCOUT_QUEUE_CONNECTION', 'redis'),
        'name' => env('SCOUT_QUEUE_NAME', 'scout-indexing'),
        'batch_size' => env('SCOUT_BATCH_SIZE', 500),
        'delay_seconds' => env('SCOUT_QUEUE_DELAY', 0),
        'tries' => env('SCOUT_QUEUE_TRIES', 3),
        'backoff_seconds' => [60, 300, 900], // 1min, 5min, 15min
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database-based full-text search with MySQL.
    |
    */

    'database' => [
        'connection' => env('SCOUT_DB_CONNECTION', 'read_replica'),
        'mode' => env('SCOUT_DB_MODE', 'NATURAL_LANGUAGE'), // NATURAL_LANGUAGE, BOOLEAN, WITH QUERY EXPANSION
        'min_search_length' => env('SCOUT_DB_MIN_SEARCH_LENGTH', 2),
        'min_token_length' => env('SCOUT_DB_MIN_TOKEN_LENGTH', 3),
        'stopwords' => env('SCOUT_DB_STOPWORDS', true),
        'custom_stopwords' => [
            'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'up', 'about', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'between', 'among', 'within', 'without', 'along', 'following', 'behind', 'beyond', 'plus', 'except', 'but', 'yet', 'nor', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'can', 'will', 'just', 'should', 'now',
        ],
        'query_expansion' => env('SCOUT_DB_QUERY_EXPANSION', true),
        'expansion_limit' => env('SCOUT_DB_EXPANSION_LIMIT', 5),
        'fuzzy_search' => env('SCOUT_DB_FUZZY', false),
        'fuzzy_threshold' => env('SCOUT_DB_FUZZY_THRESHOLD', 0.8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Elasticsearch-based search with clustering.
    |
    */

    'elasticsearch' => [
        'hosts' => [
            [
                'host' => env('ELASTICSEARCH_HOST', 'localhost'),
                'port' => env('ELASTICSEARCH_PORT', 9200),
                'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
                'user' => env('ELASTICSEARCH_USER', null),
                'pass' => env('ELASTICSEARCH_PASS', null),
                'api_id' => env('ELASTICSEARCH_API_ID', null),
                'api_key' => env('ELASTICSEARCH_API_KEY', null),
            ],
        ],
        
        'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'pageturner'),
        'shards' => env('ELASTICSEARCH_SHARDS', 3),
        'replicas' => env('ELASTICSEARCH_REPLICAS', 1),
        
        'bulk_indexing' => [
            'enabled' => env('ELASTICSEARCH_BULK_ENABLED', true),
            'batch_size' => env('ELASTICSEARCH_BULK_SIZE', 1000),
            'concurrent_requests' => env('ELASTICSEARCH_CONCURRENT', 5),
        ],
        
        'index_settings' => [
            'number_of_shards' => env('ELASTICSEARCH_SHARDS', 3),
            'number_of_replicas' => env('ELASTICSEARCH_REPLICAS', 1),
            'max_result_window' => env('ELASTICSEARCH_MAX_WINDOW', 10000),
            'refresh_interval' => env('ELASTICSEARCH_REFRESH_INTERVAL', '5s'),
        ],
        
        'analysis' => [
            'analyzer' => [
                'standard' => [
                    'tokenizer' => 'standard',
                    'filter' => ['lowercase', 'stop'],
                    'char_filter' => ['html_strip'],
                ],
                'english' => [
                    'tokenizer' => 'standard',
                    'filter' => ['lowercase', 'english_stop', 'english_stemmer'],
                    'char_filter' => ['html_strip'],
                ],
                'search_analyzer' => [
                    'tokenizer' => 'keyword',
                    'filter' => ['lowercase'],
                ],
            ],
            'normalizer' => [
                'custom_filters' => [
                    'custom_normalize' => [
                        'type' => 'char_filter',
                        'char_filter' => ['html_strip', 'mapping'],
                        'mappings' => [
                            'type' => 'pattern_replace',
                            'pattern' => '(?U)([\\p{Punct}&&[^\\p{L}&&[^\\p{N}])',
                            'replacement' => '$1$2',
                        ],
                    ],
                ],
            ],
        ],
        
        'mapping' => [
            'properties' => [
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'english',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256,
                        ],
                        'suggest' => [
                            'type' => 'completion',
                            'analyzer' => 'simple',
                        ],
                    ],
                ],
                'author' => [
                    'type' => 'text',
                    'analyzer' => 'english',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256,
                        ],
                    ],
                ],
                'description' => [
                    'type' => 'text',
                    'analyzer' => 'english',
                ],
                'isbn' => [
                    'type' => 'keyword',
                    'ignore_above' => 13,
                ],
                'category' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                ],
                'publisher' => [
                    'type' => 'text',
                    'analyzer' => 'english',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256,
                        ],
                    ],
                ],
                'publication_date' => [
                    'type' => 'date',
                    'format' => 'yyyy-MM-dd',
                ],
                'price' => [
                    'type' => 'double',
                ],
                'rating' => [
                    'type' => 'integer',
                ],
                'stock_quantity' => [
                    'type' => 'integer',
                ],
                'language' => [
                    'type' => 'keyword',
                    'ignore_above' => 50,
                ],
                'format' => [
                    'type' => 'keyword',
                    'ignore_above' => 50,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Algolia Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Algolia search service (alternative option).
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID'),
        'secret' => env('ALGOLIA_SECRET'),
        'index' => env('ALGOLIA_INDEX', 'books'),
        
        'settings' => [
            'attributesToIndex' => [
                'title',
                'author',
                'description',
                'category',
                'publisher',
                'language',
                'format',
                'rating',
                'publication_date',
                'price',
                'stock_quantity',
            ],
            'attributesForFaceting' => [
                'category',
                'language',
                'format',
                'rating',
                'price_range',
                'publication_year',
            ],
            'attributesToHighlight' => [
                'title',
                'description',
                'content',
            ],
            'ranking' => [
                'typoTolerance' => true,
                'ignorePlurals' => true,
                'removeStopWords' => true,
                'customRanking' => [
                    ['desc(publication_date)', 'desc(rating)', 'desc(stock_quantity)'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for optimizing search performance.
    |
    */

    'performance' => [
        'soft_delete' => env('SCOUT_SOFT_DELETE', true),
        'chunk_searchable' => env('SCOUT_CHUNK_SEARCHABLE', true),
        'chunk_size' => env('SCOUT_CHUNK_SIZE', 500),
        'concurrent_searches' => env('SCOUT_CONCURRENT_SEARCHES', 10),
        'search_timeout' => env('SCOUT_SEARCH_TIMEOUT', 30), // seconds
        'index_timeout' => env('SCOUT_INDEX_TIMEOUT', 300), // seconds
        'max_search_results' => env('SCOUT_MAX_RESULTS', 1000),
        'search_cache' => [
            'enabled' => env('SCOUT_SEARCH_CACHE', true),
            'ttl' => env('SCOUT_SEARCH_CACHE_TTL', 300), // 5 minutes
            'max_size' => env('SCOUT_SEARCH_CACHE_SIZE', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Features Configuration
    |--------------------------------------------------------------------------
    |
    | Configure advanced search features and behavior.
    |
    */

    'features' => [
        'fuzzy_search' => env('SCOUT_FUZZY', true),
        'auto_complete' => env('SCOUT_AUTO_COMPLETE', true),
        'spell_check' => env('SCOUT_SPELL_CHECK', true),
        'synonyms' => env('SCOUT_SYNONYMS', true),
        'boosting' => [
            'enabled' => env('SCOUT_BOOSTING', true),
            'fields' => [
                'title' => 2.0,
                'description' => 1.5,
                'author' => 1.8,
                'category' => 1.2,
                'rating' => 1.5,
                'stock_quantity' => 1.0,
            ],
            'recency_boost' => env('SCOUT_RECENCY_BOOST', true),
            'popularity_boost' => env('SCOUT_POPULARITY_BOOST', true),
        ],
        'faceting' => [
            'enabled' => env('SCOUT_FACETING', true),
            'max_facet_values' => env('SCOUT_MAX_FACET_VALUES', 50),
            'hierarchical_facets' => env('SCOUT_HIERARCHICAL_FACETS', true),
        ],
        'filtering' => [
            'range_filters' => env('SCOUT_RANGE_FILTERS', true),
            'multi_select_filters' => env('SCOUT_MULTI_SELECT_FILTERS', true),
            'dynamic_filters' => env('SCOUT_DYNAMIC_FILTERS', true),
        ],
        'sorting' => [
            'relevance_score' => env('SCOUT_RELEVANCE_SORT', true),
            'custom_sorting' => env('SCOUT_CUSTOM_SORT', true),
            'geo_sorting' => env('SCOUT_GEO_SORT', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure search analytics and logging.
    |
    */

    'analytics' => [
        'enabled' => env('SCOUT_ANALYTICS', true),
        'log_searches' => env('SCOUT_LOG_SEARCHES', true),
        'log_clicks' => env('SCOUT_LOG_CLICKS', true),
        'log_conversions' => env('SCOUT_LOG_CONVERSIONS', true),
        'track_user_sessions' => env('SCOUT_TRACK_SESSIONS', true),
        'retention_days' => env('SCOUT_ANALYTICS_RETENTION', 90),
        'aggregation_interval' => env('SCOUT_ANALYTICS_AGGREGATION', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how and when models are synced to search index.
    |
    */

    'sync' => [
        'enabled' => env('SCOUT_SYNC_ENABLED', true),
        'batch_size' => env('SCOUT_SYNC_BATCH_SIZE', 500),
        'chunk_size' => env('SCOUT_SYNC_CHUNK_SIZE', 1000),
        'queue' => env('SCOUT_SYNC_QUEUE', true),
        'soft_delete' => env('SCOUT_SYNC_SOFT_DELETE', true),
        'searchable' => [
            'App\Models\Book' => [
                'batch_import' => true,
                'queue_import' => true,
                'sync_on_create' => true,
                'sync_on_update' => true,
                'sync_on_delete' => false,
                'fields' => [
                    'title',
                    'author',
                    'description',
                    'isbn',
                    'price',
                    'category_id',
                    'publisher',
                    'publication_date',
                    'language',
                    'format',
                    'rating',
                    'stock_quantity',
                ],
            ],
            'App\Models\Category' => [
                'sync_on_create' => true,
                'sync_on_update' => true,
                'fields' => [
                    'name',
                    'description',
                ],
            ],
            'App\Models\User' => [
                'sync_on_create' => true,
                'sync_on_update' => false,
                'fields' => [
                    'first_name',
                    'last_name',
                    'email',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing Scout functionality.
    |
    */

    'testing' => [
        'enabled' => env('SCOUT_TESTING', false),
        'mock_search' => env('SCOUT_MOCK_SEARCH', false),
        'index_suffix' => env('SCOUT_TEST_INDEX_SUFFIX', '_test'),
        'force_reindex' => env('SCOUT_FORCE_REINDEX', false),
        'performance_monitoring' => env('SCOUT_TEST_PERFORMANCE', true),
    ],
];
