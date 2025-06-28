<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection
    |--------------------------------------------------------------------------
    |
    | The queue connection to use for vendor connector jobs
    |
    */
    'queue' => env('VENDOR_CONNECTOR_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for vendor API calls
    |
    */
    'rate_limits' => [
        'xero' => [
            'requests_per_minute' => 60,
            'burst' => 100,
        ],
        'quickbooks' => [
            'requests_per_minute' => 500,
            'burst' => 600,
        ],
        'sap' => [
            'requests_per_minute' => 100,
            'burst' => 120,
        ],
        'netsuite' => [
            'requests_per_minute' => 200,
            'burst' => 250,
        ],
        'dynamics' => [
            'requests_per_minute' => 300,
            'burst' => 350,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry attempts and backoff for failed API calls
    |
    */
    'retries' => [
        'max_attempts' => 3,
        'initial_delay' => 5, // seconds
        'backoff_multiplier' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for each vendor
    |
    */
    'webhooks' => [
        'xero' => [
            'enabled' => true,
            'secret' => env('XERO_WEBHOOK_SECRET'),
            'events' => ['invoices.created', 'invoices.updated'],
        ],
        'quickbooks' => [
            'enabled' => true,
            'secret' => env('QUICKBOOKS_WEBHOOK_SECRET'),
            'events' => ['invoice.create', 'invoice.update'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging settings for vendor connectors
    |
    */
    'logging' => [
        'channel' => env('VENDOR_CONNECTOR_LOG_CHANNEL', 'vendor_connectors'),
        'level' => env('VENDOR_CONNECTOR_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching settings for vendor connectors
    |
    */
    'cache' => [
        'store' => env('VENDOR_CONNECTOR_CACHE_STORE', 'redis'),
        'prefix' => 'vendor_connector:',
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping Configuration
    |--------------------------------------------------------------------------
    |
    | Default field mappings for each vendor
    |
    */
    'field_mappings' => [
        'xero' => [
            'invoice' => [
                'invoice_number' => 'InvoiceNumber',
                'invoice_date' => 'Date',
                'due_date' => 'DueDate',
                'total_amount' => 'Total',
                'subtotal' => 'SubTotal',
                'tax_amount' => 'TotalTax',
                'currency' => 'CurrencyCode',
                'status' => 'Status',
            ],
            'customer' => [
                'name' => 'Name',
                'email' => 'EmailAddress',
                'phone' => 'Phones.0.PhoneNumber',
                'address' => 'Addresses.0.AddressLine1',
                'tax_number' => 'TaxNumber',
            ],
        ],
        'quickbooks' => [
            'invoice' => [
                'invoice_number' => 'DocNumber',
                'invoice_date' => 'TxnDate',
                'due_date' => 'DueDate',
                'total_amount' => 'TotalAmt',
                'subtotal' => 'TxnTaxDetail.TotalTax',
                'currency' => 'CurrencyRef.value',
                'status' => 'EmailStatus',
            ],
            'customer' => [
                'name' => 'CustomerRef.name',
                'email' => 'BillEmail.Address',
                'phone' => 'PrimaryPhone.FreeFormNumber',
                'address' => 'BillAddr.Line1',
            ],
        ],
    ],
];
