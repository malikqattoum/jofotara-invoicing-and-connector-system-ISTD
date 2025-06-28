<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('MONITORING_ENABLED', true),

    'performance' => [
        'enabled' => true,
        'metrics' => [
            'response_time' => [
                'enabled' => true,
                'thresholds' => [
                    'warning' => 1000, // milliseconds
                    'critical' => 3000
                ]
            ],
            'memory_usage' => [
                'enabled' => true,
                'thresholds' => [
                    'warning' => 80, // percentage
                    'critical' => 95
                ]
            ],
            'cpu_usage' => [
                'enabled' => true,
                'thresholds' => [
                    'warning' => 70,
                    'critical' => 90
                ]
            ],
            'disk_usage' => [
                'enabled' => true,
                'thresholds' => [
                    'warning' => 80,
                    'critical' => 95
                ]
            ],
            'database_connections' => [
                'enabled' => true,
                'thresholds' => [
                    'warning' => 80,
                    'critical' => 95
                ]
            ]
        ],
        'collection_interval' => 60, // seconds
        'retention_days' => 30,
        'real_time_updates' => true
    ],

    'alerts' => [
        'enabled' => true,
        'channels' => ['email', 'slack', 'webhook'],
        'throttling' => [
            'enabled' => true,
            'same_alert_interval' => 300, // seconds
            'max_alerts_per_hour' => 20
        ],
        'escalation' => [
            'enabled' => true,
            'levels' => [
                'warning' => ['email'],
                'critical' => ['email', 'slack', 'sms']
            ]
        ]
    ],

    'health_checks' => [
        'enabled' => true,
        'checks' => [
            'database' => [
                'enabled' => true,
                'interval' => 30,
                'timeout' => 5,
                'critical' => true
            ],
            'cache' => [
                'enabled' => true,
                'interval' => 60,
                'timeout' => 3,
                'critical' => false
            ],
            'external_apis' => [
                'enabled' => true,
                'interval' => 120,
                'timeout' => 10,
                'critical' => false
            ],
            'file_system' => [
                'enabled' => true,
                'interval' => 300,
                'timeout' => 5,
                'critical' => true
            ]
        ],
        'endpoints' => [
            'health' => '/health',
            'ready' => '/ready',
            'status' => '/status'
        ]
    ],

    'logging' => [
        'enabled' => true,
        'channels' => ['file', 'database'],
        'levels' => ['error', 'warning', 'info'],
        'rotation' => [
            'enabled' => true,
            'max_files' => 30,
            'max_size' => '100MB'
        ]
    ],

    'analytics' => [
        'enabled' => true,
        'track_requests' => true,
        'track_performance' => true,
        'track_errors' => true,
        'track_user_actions' => true,
        'retention_days' => 90
    ],

    'profiling' => [
        'enabled' => env('PROFILING_ENABLED', false),
        'sample_rate' => 0.01, // 1% of requests
        'memory_tracking' => true,
        'query_tracking' => true,
        'slow_query_threshold' => 1000 // milliseconds
    ],

    'external_services' => [
        'new_relic' => [
            'enabled' => env('NEW_RELIC_ENABLED', false),
            'api_key' => env('NEW_RELIC_API_KEY'),
            'app_name' => env('NEW_RELIC_APP_NAME', 'Jo Invoicing')
        ],
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY')
        ],
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_DSN')
        ]
    ]

];
