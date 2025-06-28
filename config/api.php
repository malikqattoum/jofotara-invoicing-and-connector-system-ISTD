<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'gateway' => [
        'enabled' => env('API_GATEWAY_ENABLED', true),
        'rate_limiting' => env('API_RATE_LIMITING_ENABLED', true),
        'circuit_breaker' => env('API_CIRCUIT_BREAKER_ENABLED', true),
        'load_balancing' => env('API_LOAD_BALANCING_ENABLED', true),
        'caching' => env('API_CACHING_ENABLED', true),
    ],

    'rate_limits' => [
        'default' => [
            'algorithm' => 'sliding_window',
            'limit' => 1000,
            'window_seconds' => 3600,
            'burst_limit' => 100
        ],
        'endpoints' => [
            'api/v1/invoices' => [
                'algorithm' => 'token_bucket',
                'capacity' => 500,
                'refill_rate' => 10,
                'tokens_per_request' => 1
            ],
            'api/v1/sync' => [
                'algorithm' => 'adaptive',
                'base_limit' => 100,
                'window_seconds' => 3600
            ],
            'api/v1/webhooks' => [
                'algorithm' => 'fixed_window',
                'limit' => 1000,
                'window_seconds' => 3600
            ]
        ],
        'user_tiers' => [
            'basic' => ['multiplier' => 1.0],
            'pro' => ['multiplier' => 1.5],
            'premium' => ['multiplier' => 2.0],
            'enterprise' => ['multiplier' => 5.0]
        ]
    ],

    'circuit_breaker' => [
        'failure_threshold' => env('API_CIRCUIT_BREAKER_THRESHOLD', 5),
        'timeout' => env('API_CIRCUIT_BREAKER_TIMEOUT', 60), // seconds
        'success_threshold' => env('API_CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 3),
        'monitor_window' => env('API_CIRCUIT_BREAKER_MONITOR_WINDOW', 300) // seconds
    ],

    'load_balancing' => [
        'algorithm' => env('API_LOAD_BALANCING_ALGORITHM', 'weighted_round_robin'),
        'health_check' => [
            'enabled' => true,
            'interval' => 30, // seconds
            'timeout' => 5,
            'unhealthy_threshold' => 3,
            'healthy_threshold' => 2
        ]
    ],

    'services' => [
        'api/v1/invoices' => [
            [
                'url' => 'http://localhost:8000',
                'weight' => 100,
                'regions' => ['US', 'CA'],
                'health_check_url' => 'http://localhost:8000/health'
            ]
        ]
    ],

    'authentication' => [
        'methods' => ['api_key', 'bearer_token', 'basic_auth'],
        'api_key' => [
            'header' => 'X-API-Key',
            'query_param' => 'api_key'
        ],
        'bearer_token' => [
            'header' => 'Authorization',
            'prefix' => 'Bearer'
        ]
    ],

    'versioning' => [
        'strategy' => 'header', // header, url, query
        'header_name' => 'API-Version',
        'default_version' => 'v1',
        'supported_versions' => ['v1', 'v2'],
        'deprecation_warnings' => true
    ],

    'request_transformation' => [
        'enabled' => true,
        'rules' => [
            // Request transformation rules
        ]
    ],

    'response_transformation' => [
        'enabled' => true,
        'rules' => [
            // Response transformation rules
        ]
    ],

    'caching' => [
        'enabled' => true,
        'default_ttl' => 300, // seconds
        'cache_key_generator' => 'md5',
        'endpoints' => [
            'GET api/v1/invoices' => ['ttl' => 600],
            'GET api/v1/customers' => ['ttl' => 1800]
        ]
    ],

    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'request_count',
            'response_time',
            'error_rate',
            'rate_limit_hits',
            'circuit_breaker_state'
        ],
        'alerts' => [
            'high_error_rate' => true,
            'circuit_breaker_open' => true,
            'rate_limit_exceeded' => true,
            'slow_response_time' => true
        ],
        'thresholds' => [
            'error_rate_warning' => 5, // percentage
            'error_rate_critical' => 10,
            'response_time_warning' => 1000, // milliseconds
            'response_time_critical' => 3000
        ]
    ],

    'security' => [
        'cors' => [
            'enabled' => true,
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
            'max_age' => 86400
        ],
        'rate_limit_by_ip' => true,
        'request_size_limit' => '10MB',
        'request_timeout' => 30 // seconds
    ],

    'documentation' => [
        'auto_generate' => true,
        'format' => 'openapi_3_0',
        'include_examples' => true,
        'include_schemas' => true
    ]

];
