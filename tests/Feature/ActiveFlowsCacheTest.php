<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Trigger\ActiveFlowsCache;
use Grazulex\AutoBuilder\Trigger\TriggerManager;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'cache.default' => 'array',
        'autobuilder.cache.enabled' => true,
        'autobuilder.cache.store' => null,
        'autobuilder.cache.key' => 'autobuilder:active_flows',
        'autobuilder.cache.ttl' => 86400,
    ]);

    Cache::flush();
});

function scheduledFlow(array $attributes = []): Flow
{
    return Flow::create(array_merge([
        'name' => 'Scheduled flow',
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

function freshTriggerManager(): TriggerManager
{
    // A new instance per "request", like a real boot.
    return new TriggerManager(app(BrickRegistry::class), app(ActiveFlowsCache::class));
}

function countFlowQueries(callable $callback): int
{
    $count = 0;

    DB::listen(function ($query) use (&$count): void {
        if (str_contains($query->sql, 'autobuilder_flows')) {
            $count++;
        }
    });

    $callback();

    return $count;
}

it('caches active flows so a second boot does not query the database', function () {
    $flow = scheduledFlow();
    scheduledFlow(['name' => 'Inactive', 'active' => false]);

    $firstQueries = countFlowQueries(fn () => freshTriggerManager()->bootActiveFlows());
    expect($firstQueries)->toBe(1);

    $manager = freshTriggerManager();
    $secondQueries = countFlowQueries(fn () => $manager->bootActiveFlows());

    expect($secondQueries)->toBe(0)
        ->and(array_keys($manager->getRegisteredTriggers()))->toBe([$flow->id]);
});

it('stores only plain serializable arrays in the cache', function () {
    scheduledFlow();

    freshTriggerManager()->bootActiveFlows();

    $payload = Cache::get('autobuilder:active_flows');

    expect($payload)->toBeArray()->toHaveCount(1)
        ->and($payload[0])->toBeArray()
        ->and(unserialize(serialize($payload)))->toBe($payload);

    array_walk_recursive($payload, function ($value): void {
        expect(is_object($value))->toBeFalse();
    });
});

it('hydrates cached flows with their casts', function () {
    $flow = scheduledFlow();
    freshTriggerManager()->bootActiveFlows();

    $cached = app(ActiveFlowsCache::class)->get();

    expect($cached)->toHaveCount(1)
        ->and($cached->first())->toBeInstanceOf(Flow::class)
        ->and($cached->first()->id)->toBe($flow->id)
        ->and($cached->first()->active)->toBeTrue()
        ->and($cached->first()->nodes)->toBe($flow->nodes);
});

it('invalidates the cache when a flow changes', function (Closure $change) {
    $flow = scheduledFlow();
    freshTriggerManager()->bootActiveFlows();
    expect(Cache::has('autobuilder:active_flows'))->toBeTrue();

    $change($flow);

    expect(Cache::has('autobuilder:active_flows'))->toBeFalse();
})->with([
    'created' => [fn (Flow $flow) => scheduledFlow(['name' => 'Another'])],
    'updated' => [fn (Flow $flow) => $flow->update(['name' => 'Renamed'])],
    'deactivated' => [fn (Flow $flow) => $flow->deactivate()],
    'activated' => [function (Flow $flow): void {
        $flow->deactivate();
        freshTriggerManager()->bootActiveFlows();
        $flow->activate();
    }],
    'deleted' => [fn (Flow $flow) => $flow->delete()],
    'force deleted' => [fn (Flow $flow) => $flow->forceDelete()],
    'restored' => [function (Flow $flow): void {
        $flow->delete();
        freshTriggerManager()->bootActiveFlows();
        $flow->restore();
    }],
]);

it('picks up changes on the next boot after invalidation', function () {
    $flow = scheduledFlow();
    freshTriggerManager()->bootActiveFlows();

    $flow->deactivate();
    $other = scheduledFlow(['name' => 'New one']);

    $manager = freshTriggerManager();
    $manager->bootActiveFlows();

    expect(array_keys($manager->getRegisteredTriggers()))->toBe([$other->id]);
});

it('does not break the request when the flows table does not exist', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'Could not load active flows'));

    Schema::disableForeignKeyConstraints();
    Schema::drop('autobuilder_flows');
    Schema::enableForeignKeyConstraints();

    $manager = freshTriggerManager();
    $manager->bootActiveFlows();

    expect($manager->getRegisteredTriggers())->toBe([])
        ->and(Cache::has('autobuilder:active_flows'))->toBeFalse();
});

it('does not break the request when the database is unreachable', function () {
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

    $manager = freshTriggerManager();

    try {
        expect(fn () => $manager->bootActiveFlows())->not->toThrow(Throwable::class)
            ->and($manager->getRegisteredTriggers())->toBe([])
            ->and(Cache::has('autobuilder:active_flows'))->toBeFalse();
    } finally {
        // Give the test transaction back to RefreshDatabase.
        config(['database.default' => 'testing']);
    }
});

it('falls back to the database when the cache store fails', function () {
    $flow = scheduledFlow();

    $factory = Mockery::mock(CacheFactory::class);
    $factory->shouldReceive('store')->andThrow(new RuntimeException('Redis is down'));

    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'Redis is down'));
    Log::shouldReceive('info', 'debug')->zeroOrMoreTimes();

    $manager = new TriggerManager(app(BrickRegistry::class), new ActiveFlowsCache($factory));
    $manager->bootActiveFlows();

    expect(array_keys($manager->getRegisteredTriggers()))->toBe([$flow->id]);
});

it('ignores a corrupted cache entry and reloads from the database', function () {
    $flow = scheduledFlow();
    Cache::forever('autobuilder:active_flows', 'not-a-list');

    $manager = freshTriggerManager();
    $manager->bootActiveFlows();

    expect(array_keys($manager->getRegisteredTriggers()))->toBe([$flow->id])
        ->and(Cache::get('autobuilder:active_flows'))->toBeArray()->toHaveCount(1);
});

it('queries the database on every boot when the cache is disabled', function () {
    config(['autobuilder.cache.enabled' => false]);
    scheduledFlow();

    $queries = countFlowQueries(function (): void {
        freshTriggerManager()->bootActiveFlows();
        freshTriggerManager()->bootActiveFlows();
    });

    expect($queries)->toBe(2)
        ->and(Cache::has('autobuilder:active_flows'))->toBeFalse();
});

it('uses the configured cache store and key', function () {
    config([
        'cache.stores.flows' => ['driver' => 'array'],
        'autobuilder.cache.store' => 'flows',
        'autobuilder.cache.key' => 'my-app:flows',
    ]);
    scheduledFlow();

    freshTriggerManager()->bootActiveFlows();

    expect(Cache::store('flows')->has('my-app:flows'))->toBeTrue()
        ->and(Cache::has('my-app:flows'))->toBeFalse()
        ->and(Cache::has('autobuilder:active_flows'))->toBeFalse();
});

it('treats a null or zero ttl as forever', function (mixed $ttl, ?int $expected) {
    config(['autobuilder.cache.ttl' => $ttl]);

    expect(app(ActiveFlowsCache::class)->ttl())->toBe($expected);
})->with([
    'null' => [null, null],
    'zero' => [0, null],
    'string zero' => ['0', null],
    'one hour' => [3600, 3600],
    'env string' => ['600', 600],
]);

it('clears the cache with the artisan command', function () {
    scheduledFlow();
    freshTriggerManager()->bootActiveFlows();
    expect(Cache::has('autobuilder:active_flows'))->toBeTrue();

    $this->artisan('autobuilder:clear-cache')
        ->expectsOutputToContain('cache cleared')
        ->assertSuccessful();

    expect(Cache::has('autobuilder:active_flows'))->toBeFalse();
});
