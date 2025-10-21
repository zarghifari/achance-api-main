<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'memory_threshold' => env('MEMORY_THRESHOLD', 256), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Query Optimization
    |--------------------------------------------------------------------------
    */

    'database' => [
        'connection_pooling' => true,
        'persistent_connections' => true,
        'query_cache_enabled' => true,
        'query_cache_ttl' => 3600, // seconds
        'eager_loading' => [
            'default_relations' => ['user', 'course'],
            'max_relations' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Strategy
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'default_ttl' => 3600,
        'tags_enabled' => true,
        'strategies' => [
            'queries' => [
                'enabled' => true,
                'ttl' => 1800,
                'prefix' => 'query_cache:',
            ],
            'api_responses' => [
                'enabled' => true,
                'ttl' => 300,
                'prefix' => 'api_cache:',
            ],
            'static_data' => [
                'enabled' => true,
                'ttl' => 86400,
                'prefix' => 'static_cache:',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'async_processing' => [
            'bulk_imports' => true,
            'image_processing' => true,
            'notifications' => true,
        ],
        'batch_size' => 100,
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    */

    'images' => [
        'compression_quality' => 85,
        'max_width' => 1920,
        'max_height' => 1080,
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],
        ],
        'lazy_loading' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'api_requests' => 60, // per minute
        'bulk_imports' => 5,  // per minute
        'file_uploads' => 10, // per minute
    ],

];
