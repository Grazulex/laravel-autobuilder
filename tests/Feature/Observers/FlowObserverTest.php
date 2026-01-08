<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnWebhookReceived;
use Grazulex\AutoBuilder\Models\Flow;

it('extracts trigger_type and trigger_config when creating a flow with a trigger', function () {
    $flow = Flow::create([
        'name' => 'Scheduled Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnSchedule::class,
                    'config' => [
                        'frequency' => 'everyMinute',
                        'timezone' => 'Europe/Brussels',
                    ],
                ],
            ],
        ],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->trigger_type)->toBe(OnSchedule::class);
    expect($flow->trigger_config)->toBe([
        'frequency' => 'everyMinute',
        'timezone' => 'Europe/Brussels',
    ]);
});

it('extracts webhook_path when creating a flow with OnWebhookReceived trigger', function () {
    $flow = Flow::create([
        'name' => 'Webhook Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnWebhookReceived::class,
                    'config' => [
                        'path' => 'my-custom-webhook',
                        'method' => 'POST',
                        'secret' => 'my-secret',
                    ],
                ],
            ],
        ],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->trigger_type)->toBe(OnWebhookReceived::class);
    expect($flow->trigger_config)->toBe([
        'path' => 'my-custom-webhook',
        'method' => 'POST',
        'secret' => 'my-secret',
    ]);
    expect($flow->webhook_path)->toBe('my-custom-webhook');
});

it('clears webhook_path when trigger is changed from OnWebhookReceived to another', function () {
    $flow = Flow::create([
        'name' => 'Webhook Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnWebhookReceived::class,
                    'config' => [
                        'path' => 'my-webhook',
                        'method' => 'POST',
                    ],
                ],
            ],
        ],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->webhook_path)->toBe('my-webhook');

    // Update to a different trigger
    $flow->update([
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnSchedule::class,
                    'config' => [
                        'frequency' => 'everyMinute',
                    ],
                ],
            ],
        ],
    ]);

    expect($flow->fresh()->webhook_path)->toBeNull();
    expect($flow->fresh()->trigger_type)->toBe(OnSchedule::class);
});

it('updates webhook_path when webhook path config changes', function () {
    $flow = Flow::create([
        'name' => 'Webhook Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnWebhookReceived::class,
                    'config' => [
                        'path' => 'original-path',
                        'method' => 'POST',
                    ],
                ],
            ],
        ],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->webhook_path)->toBe('original-path');

    // Update the webhook path
    $flow->update([
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnWebhookReceived::class,
                    'config' => [
                        'path' => 'new-path',
                        'method' => 'POST',
                    ],
                ],
            ],
        ],
    ]);

    expect($flow->fresh()->webhook_path)->toBe('new-path');
});

it('clears trigger data when flow has no trigger node', function () {
    $flow = Flow::create([
        'name' => 'No Trigger Flow',
        'nodes' => [
            [
                'id' => 'action-1',
                'type' => 'action',
                'data' => [
                    'brick' => 'SomeAction',
                    'config' => [],
                ],
            ],
        ],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->trigger_type)->toBeNull();
    expect($flow->trigger_config)->toBeNull();
    expect($flow->webhook_path)->toBeNull();
});

it('can find flow by webhook_path after creation', function () {
    Flow::create([
        'name' => 'Webhook Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnWebhookReceived::class,
                    'config' => [
                        'path' => 'unique-webhook-path',
                        'method' => 'POST',
                    ],
                ],
            ],
        ],
        'edges' => [],
        'active' => true,
    ]);

    $foundFlow = Flow::where('webhook_path', 'unique-webhook-path')
        ->where('active', true)
        ->first();

    expect($foundFlow)->not->toBeNull();
    expect($foundFlow->name)->toBe('Webhook Flow');
});
