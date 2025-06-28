<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'default_timeout' => 30,
    'default_retry_attempts' => 3,
    'default_rate_limit_delay' => 1,

    /*
    |--------------------------------------------------------------------------
    | Vendor-Specific Settings
    |--------------------------------------------------------------------------
    */
    'vendors' => [
        'quickbooks' => [
            'name' => 'QuickBooks Online',
            'base_url' => 'https://sandbox-quickbooks.api.intuit.com',
            'production_url' => 'https://quickbooks.api.intuit.com',
            'oauth_version' => '2.0',
            'rate_limit' => [
                'requests_per_minute' => 100,
                'delay_seconds' => 1
            ],
            'webhook_verification' => true
        ],

        'xero' => [
            'name' => 'Xero',
            'base_url' => 'https://api.xero.com/api.xro/2.0',
            'oauth_version' => '2.0',
            'rate_limit' => [
                'requests_per_minute' => 60,
                'delay_seconds' => 1
            ],
            'webhook_verification' => true
        ],

        'sap' => [
            'name' => 'SAP Business One',
            'oauth_version' => 'session',
            'rate_limit' => [
                'requests_per_minute' => 30,
                'delay_seconds' => 2
            ],
            'webhook_verification' => false
        ],

        'netsuite' => [
            'name' => 'Oracle NetSuite',
            'oauth_version' => '1.0',
            'rate_limit' => [
                'requests_per_minute' => 40,
                'delay_seconds' => 1.5
            ],
            'webhook_verification' => true
        ],

        'dynamics' => [
            'name' => 'Microsoft Dynamics 365',
            'base_url' => 'https://api.businesscentral.dynamics.com/v2.0',
            'oauth_version' => '2.0',
            'rate_limit' => [
                'requests_per_minute' => 60,
                'delay_seconds' => 1
            ],
            'webhook_verification' => true
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'default_page_size' => 50,
        'max_page_size' => 100,
        'batch_size' => 25,
        'queue_connection' => 'default',
        'queue_name' => 'vendor-sync'
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'token_cache_duration' => 3600, // 1 hour
        'rate_limit_cache_duration' => 60, // 1 minute
        'prefix' => 'vendor_integration'
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'verify_signatures' => true,
        'timeout' => 10,
        'retry_attempts' => 3
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channel' => 'vendor-integrations'
    ]
];
