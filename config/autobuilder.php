<?php

declare(strict_types=1);

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
    | Active Flows Cache
    |--------------------------------------------------------------------------
    |
    | Triggers of active flows are registered on every HTTP request. To avoid
    | a database query each time, the list of active flows is cached. The
    | cache is cleared automatically whenever a flow is created, updated,
    | activated, deactivated, deleted or restored through Eloquent. If you
    | change flows another way (raw SQL, mass updates), run:
    |
    |     php artisan autobuilder:clear-cache
    |
    | store: cache store name from config/cache.php (null = default store).
    |        With several servers, use a shared store (redis, database...).
    | ttl:   seconds to keep the list; null or 0 = until invalidated.
    |
    */
    'cache' => [
        'enabled' => env('AUTOBUILDER_CACHE_ENABLED', true),
        'store' => env('AUTOBUILDER_CACHE_STORE'),
        'key' => env('AUTOBUILDER_CACHE_KEY', 'autobuilder:active_flows'),
        'ttl' => env('AUTOBUILDER_CACHE_TTL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Configure scheduled flow execution. The autobuilder:schedule-run command
    | will be registered with Laravel's scheduler and run every minute.
    |
    | IMPORTANT: You must have Laravel's scheduler running for scheduled flows
    | to work. Add this to your crontab:
    |
    | * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    |
    */
    'scheduling' => [
        'enabled' => env('AUTOBUILDER_SCHEDULING_ENABLED', true),
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
        'retry_attempts' => 3, // Retries for a failing action (0 disables), overridable per node
        'retry_delay' => 60, // Seconds between action retries, overridable per node
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'activated' => env('AUTOBUILDER_LOG', true),
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
