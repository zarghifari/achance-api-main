<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for N+1 query detection and optimization features.
    |
    */

    // Enable N+1 query detection (only in debug mode)
    'enable_detection' => env('QUERY_OPTIMIZATION_DETECTION', true),

    // Threshold for slow query detection (in milliseconds)
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 100),

    // Maximum number of queries before triggering N+1 warning
    'max_queries_threshold' => env('MAX_QUERIES_THRESHOLD', 15),

    // Log slow queries to Laravel log
    'log_slow_queries' => env('LOG_SLOW_QUERIES', true),

    // Log potential N+1 queries
    'log_n1_queries' => env('LOG_N1_QUERIES', true),

    // Add query count to response headers (debug mode only)
    'add_headers' => env('QUERY_ADD_HEADERS', true),

    // Middleware routes to exclude from N+1 detection
    'excluded_routes' => [
        'telescope/*',
        'horizon/*',
        '_debugbar/*',
    ],

    // Common eager loading patterns for different models
    'eager_loading_patterns' => [
        'Course' => ['modules.lessons.epub', 'modules.tasks'],
        'Module' => ['course', 'lessons.epub', 'tasks'],
        'Lesson' => ['module.course', 'epub'],
        'Quiz' => ['quizQuestions.quizAnswers'],
        'User' => ['userActivities'],
    ],
];
