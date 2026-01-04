<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'autobuilder',
        'middleware' => ['web', 'autobuilder.auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */
    'authorization' => [
        'gate' => 'access-autobuilder', // Set to null to disable
        'super_admins' => [], // User IDs with full access
    ],

    /*
    |--------------------------------------------------------------------------
    | Brick Discovery
    |--------------------------------------------------------------------------
    */
    'bricks' => [
        'discover' => true,
        'paths' => [
            app_path('AutoBuilder'),
        ],
        'namespaces' => [
            'App\\AutoBuilder',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Bricks
    |--------------------------------------------------------------------------
    */
    'built_in' => [
        'triggers' => true,
        'conditions' => true,
        'actions' => true,

        // Disable specific bricks
        'disabled' => [
            // 'Grazulex\\AutoBuilder\\BuiltIn\\Actions\\ExecuteCode',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Triggers
    |--------------------------------------------------------------------------
    */
    'triggers' => [
        'enabled' => env('AUTOBUILDER_TRIGGERS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Settings
    |--------------------------------------------------------------------------
    */
    'execution' => [
        'async' => env('AUTOBUILDER_ASYNC', true), // Queue flow execution
        'queue' => env('AUTOBUILDER_QUEUE', 'default'),
        'timeout' => 300, // seconds
        'max_nodes' => 100, // Max total node executions per flow run
        'max_node_executions' => 10, // Max times a single node can execute (loop detection)
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('AUTOBUILDER_LOG_CHANNEL', 'stack'),
        'retention_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'driver' => 'inertia', // 'inertia', 'livewire', or 'blade'
        'theme' => 'light', // 'light', 'dark', 'auto'
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'allow_custom_code' => false, // Enable CustomClosure brick
        'webhook_signature_header' => 'X-Webhook-Secret',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('AUTOBUILDER_RATE_LIMITING', true),
        'webhooks' => [
            'max_attempts' => env('AUTOBUILDER_WEBHOOK_RATE_LIMIT', 60), // per minute
            'decay_minutes' => 1,
        ],
    ],

];
