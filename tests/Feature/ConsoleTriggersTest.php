<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\AutoBuilderServiceProvider;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Trigger\ActiveFlowsCache;
use Grazulex\AutoBuilder\Trigger\TriggerManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'cache.default' => 'array',
        'autobuilder.triggers.enabled' => true,
        'autobuilder.triggers.console' => true,
    ]);

    Cache::flush();
});

function consoleTriggerFlow(array $attributes = []): Flow
{
    return Flow::create(array_merge([
        'name' => 'Console flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'data' => [
                    'brick' => OnSchedule::class,
                    'config' => ['frequency' => 'everyMinute'],
                ],
            ],
        ],
        'edges' => [],
        'active' => true,
    ], $attributes));
}

/**
 * Run the provider's trigger boot step again, like a new process would.
 * The application is already booted, so the booted() callback runs at once.
 */
function bootProviderTriggers(): void
{
    $provider = app()->getProvider(AutoBuilderServiceProvider::class);

    $method = new ReflectionMethod($provider, 'bootTriggers');
    $method->invoke($provider);
}

function setRunningInConsole(bool $console): void
{
    $property = new ReflectionProperty(app(), 'isRunningInConsole');
    $property->setValue(app(), $console);
}

function newConsoleTriggerManager(): TriggerManager
{
    return new TriggerManager(app(BrickRegistry::class), app(ActiveFlowsCache::class));
}

function dropFlowsTable(): void
{
    Schema::disableForeignKeyConstraints();
    Schema::drop('autobuilder_flows');
    Schema::enableForeignKeyConstraints();
}

it('registers triggers of active flows in console processes', function () {
    expect(app()->runningInConsole())->toBeTrue();

    $flow = consoleTriggerFlow();
    consoleTriggerFlow(['name' => 'Inactive', 'active' => false]);

    bootProviderTriggers();

    expect(array_keys(app(TriggerManager::class)->getRegisteredTriggers()))->toBe([$flow->id]);
});

it('does not register triggers in console when triggers.console is false', function () {
    config(['autobuilder.triggers.console' => false]);
    consoleTriggerFlow();

    bootProviderTriggers();

    expect(app(TriggerManager::class)->getRegisteredTriggers())->toBe([]);
});

it('still registers triggers in HTTP requests when triggers.console is false', function () {
    config(['autobuilder.triggers.console' => false]);
    $flow = consoleTriggerFlow();

    setRunningInConsole(false);

    try {
        bootProviderTriggers();
    } finally {
        setRunningInConsole(true);
    }

    expect(array_keys(app(TriggerManager::class)->getRegisteredTriggers()))->toBe([$flow->id]);
});

it('does not register triggers anywhere when triggers are disabled', function (bool $console) {
    config(['autobuilder.triggers.enabled' => false]);
    consoleTriggerFlow();

    setRunningInConsole($console);

    try {
        bootProviderTriggers();
    } finally {
        setRunningInConsole(true);
    }

    expect(app(TriggerManager::class)->getRegisteredTriggers())->toBe([]);
})->with([
    'console' => [true],
    'http' => [false],
]);

it('stays silent in console when the flows table does not exist yet', function () {
    dropFlowsTable();

    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning', 'error')->never();
    Log::shouldReceive('debug')->once()->withArgs(fn (string $message): bool => str_contains($message, 'not migrated yet'));

    bootProviderTriggers();

    expect(app(TriggerManager::class)->getRegisteredTriggers())->toBe([])
        ->and(Cache::has('autobuilder:active_flows'))->toBeFalse();
});

it('bootActiveFlowsIfInstalled stays silent only for a missing flows table', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'Could not load active flows'));

    config([
        'database.connections.unreachable' => [
            'driver' => 'sqlite',
            'database' => '/nonexistent/autobuilder/database.sqlite',
            'prefix' => '',
        ],
        'database.default' => 'unreachable',
    ]);

    $manager = newConsoleTriggerManager();

    try {
        expect(fn () => $manager->bootActiveFlowsIfInstalled())->not->toThrow(Throwable::class)
            ->and($manager->getRegisteredTriggers())->toBe([]);
    } finally {
        // Give the test transaction back to RefreshDatabase.
        config(['database.default' => 'testing']);
    }
});

it('bootActiveFlowsIfInstalled registers active flows like bootActiveFlows', function () {
    $flow = consoleTriggerFlow();

    $manager = newConsoleTriggerManager();
    $manager->bootActiveFlowsIfInstalled();

    expect(array_keys($manager->getRegisteredTriggers()))->toBe([$flow->id]);
});

it('bootActiveFlows still warns when the flows table does not exist', function () {
    dropFlowsTable();

    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'Could not load active flows'));

    $manager = newConsoleTriggerManager();
    $manager->bootActiveFlows();

    expect($manager->getRegisteredTriggers())->toBe([]);
});

it('ships the console option enabled by default', function () {
    $config = require __DIR__.'/../../config/autobuilder.php';

    expect($config['triggers'])->toHaveKey('console')
        ->and((bool) $config['triggers']['console'])->toBeTrue();
});
