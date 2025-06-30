<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS Connector Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Universal POS Connector system
    |
    */

    /**
     * Automatically create invoices from POS transactions
     */
    'auto_create_invoices' => env('POS_AUTO_CREATE_INVOICES', true),

    /**
     * Default sync interval for new customers (in seconds)
     */
    'default_sync_interval' => env('POS_DEFAULT_SYNC_INTERVAL', 300),

    /**
     * Maximum transactions to process in a single batch
     */
    'max_batch_size' => env('POS_MAX_BATCH_SIZE', 100),

    /**
     * Connector heartbeat timeout (minutes)
     * Connectors not seen within this time are marked as inactive
     */
    'heartbeat_timeout' => env('POS_HEARTBEAT_TIMEOUT', 10),

    /**
     * Support contact information
     */
    'support' => [
        'email' => env('POS_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@example.com')),
        'phone' => env('POS_SUPPORT_PHONE', '+1-800-SUPPORT'),
    ],

    /**
     * Package generation settings
     */
    'package' => [
        'temp_directory' => storage_path('app/temp/pos-packages'),
        'executable_path' => base_path('pos-connector/dist/JoFotara_POS_Connector.exe'),
        'include_readme' => true,
        'include_support_info' => true,
    ],

    /**
     * Business types available for POS customers
     */
    'business_types' => [
        'restaurant' => 'Restaurant',
        'retail' => 'Retail Store',
        'medical' => 'Medical/Healthcare',
        'automotive' => 'Automotive',
        'beauty' => 'Beauty/Salon',
        'professional' => 'Professional Services',
        'other' => 'Other',
    ],

    /**
     * Default sync intervals available (in seconds)
     */
    'sync_intervals' => [
        60 => '1 minute (Fast)',
        300 => '5 minutes (Default)',
        600 => '10 minutes',
        1800 => '30 minutes',
        3600 => '1 hour (Slow)',
    ],

    /**
     * Transaction processing settings
     */
    'transactions' => [
        'allow_duplicates' => false,
        'require_customer_info' => false,
        'default_payment_status' => 'completed',
        'auto_process' => true,
    ],

    /**
     * Logging settings for POS operations
     */
    'logging' => [
        'enabled' => env('POS_LOGGING_ENABLED', true),
        'level' => env('POS_LOG_LEVEL', 'info'),
        'channel' => env('POS_LOG_CHANNEL', 'single'),
    ],
];
