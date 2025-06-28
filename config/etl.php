<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ETL Data Pipeline Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ETL_ENABLED', true),

    'settings' => [
        'max_execution_time' => env('ETL_MAX_EXECUTION_TIME', 3600), // seconds
        'default_batch_size' => env('ETL_BATCH_SIZE', 1000),
        'max_memory_usage' => env('ETL_MAX_MEMORY', '512M'),
        'parallel_processing' => env('ETL_PARALLEL_PROCESSING', true),
        'max_parallel_jobs' => env('ETL_MAX_PARALLEL_JOBS', 4),
    ],

    'transformation_rules' => [
        'field_mapping' => [
            'max_mappings' => 100,
            'allow_nested' => true,
            'default_behavior' => 'exclude_unmapped'
        ],
        'data_type_conversion' => [
            'strict_mode' => false,
            'error_on_invalid' => false,
            'supported_types' => ['string', 'integer', 'float', 'boolean', 'date', 'datetime']
        ],
        'data_cleansing' => [
            'operations' => [
                'trim', 'uppercase', 'lowercase', 'remove_special_chars',
                'normalize_phone', 'normalize_email', 'standardize_dates'
            ]
        ],
        'data_enrichment' => [
            'external_apis' => true,
            'lookup_tables' => true,
            'calculated_fields' => true
        ]
    ],

    'data_quality_rules' => [
        'completeness' => [
            'weight' => 0.3,
            'threshold' => 90, // percentage
            'required_fields' => []
        ],
        'accuracy' => [
            'weight' => 0.3,
            'threshold' => 95,
            'validation_rules' => []
        ],
        'consistency' => [
            'weight' => 0.2,
            'threshold' => 85,
            'cross_field_rules' => []
        ],
        'validity' => [
            'weight' => 0.2,
            'threshold' => 90,
            'format_rules' => []
        ]
    ],

    'data_sources' => [
        'database' => [
            'timeout' => 300,
            'retry_attempts' => 3,
            'supported_types' => ['mysql', 'postgresql', 'sqlite', 'sqlserver']
        ],
        'api' => [
            'timeout' => 60,
            'retry_attempts' => 3,
            'rate_limit' => 1000, // requests per hour
            'supported_auth' => ['bearer', 'basic', 'api_key', 'oauth2']
        ],
        'file' => [
            'max_file_size' => '100MB',
            'supported_formats' => ['csv', 'json', 'xml', 'excel'],
            'encoding' => 'UTF-8'
        ],
        'integration' => [
            'timeout' => 120,
            'batch_size' => 500
        ]
    ],

    'destinations' => [
        'database' => [
            'batch_insert' => true,
            'upsert_support' => true,
            'transaction_size' => 1000
        ],
        'api' => [
            'batch_requests' => true,
            'max_batch_size' => 100
        ],
        'file' => [
            'formats' => ['csv', 'json', 'xml'],
            'compression' => ['gzip', 'zip']
        ],
        'cache' => [
            'ttl' => 3600,
            'layers' => ['memory', 'redis']
        ]
    ],

    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'records_processed',
            'processing_time',
            'error_rate',
            'data_quality_score',
            'throughput'
        ],
        'alerts' => [
            'execution_timeout' => true,
            'high_error_rate' => true,
            'low_data_quality' => true,
            'performance_degradation' => true
        ],
        'thresholds' => [
            'error_rate_critical' => 10, // percentage
            'quality_score_warning' => 80,
            'quality_score_critical' => 70,
            'processing_time_warning' => 3600 // seconds
        ]
    ],

    'optimization' => [
        'auto_batch_sizing' => true,
        'parallel_extraction' => true,
        'memory_optimization' => true,
        'compression' => true,
        'indexing' => true
    ],

    'security' => [
        'encrypt_sensitive_data' => true,
        'audit_trail' => true,
        'data_masking' => [
            'enabled' => true,
            'fields' => ['ssn', 'credit_card', 'email'],
            'method' => 'partial'
        ]
    ]

];
