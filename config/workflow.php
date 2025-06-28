<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workflow Engine Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('WORKFLOW_ENABLED', true),

    'settings' => [
        'max_execution_time' => env('WORKFLOW_MAX_EXECUTION_TIME', 300), // seconds
        'max_concurrent_executions' => env('WORKFLOW_MAX_CONCURRENT', 50),
        'retry_attempts' => env('WORKFLOW_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WORKFLOW_RETRY_DELAY', 60), // seconds
    ],

    'allowed_scripts' => [
        'data_validation',
        'email_formatting',
        'custom_calculations',
        'data_transformation'
    ],

    'step_types' => [
        'sync_integration' => [
            'class' => 'App\Workflows\Steps\SyncIntegrationStep',
            'timeout' => 120,
            'retryable' => true
        ],
        'send_notification' => [
            'class' => 'App\Workflows\Steps\SendNotificationStep',
            'timeout' => 30,
            'retryable' => true
        ],
        'data_transformation' => [
            'class' => 'App\Workflows\Steps\DataTransformationStep',
            'timeout' => 180,
            'retryable' => false
        ],
        'conditional_branch' => [
            'class' => 'App\Workflows\Steps\ConditionalBranchStep',
            'timeout' => 10,
            'retryable' => false
        ],
        'http_request' => [
            'class' => 'App\Workflows\Steps\HttpRequestStep',
            'timeout' => 60,
            'retryable' => true
        ],
        'delay' => [
            'class' => 'App\Workflows\Steps\DelayStep',
            'timeout' => null,
            'retryable' => false
        ],
        'approval_gate' => [
            'class' => 'App\Workflows\Steps\ApprovalGateStep',
            'timeout' => null,
            'retryable' => false
        ],
        'custom_script' => [
            'class' => 'App\Workflows\Steps\CustomScriptStep',
            'timeout' => 300,
            'retryable' => false
        ]
    ],

    'triggers' => [
        'invoice.created',
        'invoice.updated',
        'invoice.paid',
        'integration.synced',
        'integration.failed',
        'data.quality.check',
        'system.alert',
        'user.action',
        'schedule.daily',
        'schedule.weekly',
        'schedule.monthly'
    ],

    'templates' => [
        'invoice_sync' => [
            'name' => 'Invoice Sync Workflow',
            'description' => 'Automatically sync invoices from integrations',
            'trigger_event' => 'invoice.created',
            'steps' => [
                [
                    'name' => 'Sync Integration',
                    'type' => 'sync_integration',
                    'configuration' => ['integration_id' => null],
                    'order' => 1
                ],
                [
                    'name' => 'Send Notification',
                    'type' => 'send_notification',
                    'configuration' => [
                        'event' => 'invoice.synced',
                        'channels' => ['email']
                    ],
                    'order' => 2
                ]
            ]
        ],
        'data_quality_check' => [
            'name' => 'Data Quality Check',
            'description' => 'Monitor and alert on data quality issues',
            'trigger_event' => 'data.quality.check',
            'steps' => [
                [
                    'name' => 'Analyze Data Quality',
                    'type' => 'data_transformation',
                    'configuration' => [
                        'transformations' => [
                            ['operation' => 'quality_score']
                        ]
                    ],
                    'order' => 1
                ],
                [
                    'name' => 'Quality Gate',
                    'type' => 'conditional_branch',
                    'configuration' => [
                        'condition' => ['field' => 'quality_score', 'operator' => '<', 'value' => 80]
                    ],
                    'order' => 2
                ],
                [
                    'name' => 'Send Alert',
                    'type' => 'send_notification',
                    'configuration' => [
                        'event' => 'data.quality.alert',
                        'channels' => ['email', 'slack']
                    ],
                    'conditions' => [['field' => 'branch_results.2', 'operator' => '=', 'value' => true]],
                    'order' => 3
                ]
            ]
        ]
    ],

    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'execution_time',
            'success_rate',
            'error_rate',
            'step_performance'
        ],
        'alerts' => [
            'execution_timeout' => true,
            'high_failure_rate' => true,
            'stuck_workflows' => true
        ]
    ]

];
