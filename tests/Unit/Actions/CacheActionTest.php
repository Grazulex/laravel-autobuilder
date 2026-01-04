<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\CacheAction;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();

    // Use array driver for testing
    config(['cache.default' => 'array']);
    Cache::flush();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(CacheAction::class);

        expect($brick->name())->toBe('Cache');
        expect($brick->category())->toBe('Data');
        expect($brick->icon())->toBe('database');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(CacheAction::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('operation');
        expect($fieldNames)->toContain('key');
        expect($fieldNames)->toContain('value');
        expect($fieldNames)->toContain('ttl');
        expect($fieldNames)->toContain('amount');
        expect($fieldNames)->toContain('store_as');
        expect($fieldNames)->toContain('store');
    });
});

// =============================================================================
// Put Operation Tests
// =============================================================================

describe('put operation', function () {
    it('stores a string value', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'test_key',
            'value' => 'test_value',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('test_key'))->toBe('test_value');
    });

    it('stores a numeric value', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'counter',
            'value' => '42',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('counter'))->toBe('42');
    });

    it('stores JSON object as array', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'user_data',
            'value' => '{"name": "Alice", "age": 30}',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        $cached = Cache::get('user_data');
        expect($cached)->toBe(['name' => 'Alice', 'age' => 30]);
    });

    it('stores JSON array', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'list',
            'value' => '[1, 2, 3]',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('list'))->toBe([1, 2, 3]);
    });

    it('stores value forever when ttl is 0', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'forever_key',
            'value' => 'forever_value',
            'ttl' => 0,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('forever_key'))->toBe('forever_value');
    });

    it('resolves value from variable', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'dynamic_key',
            'value' => '{{ user_name }}',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', ['user_name' => 'Bob']);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('dynamic_key'))->toBe('Bob');
    });

    it('resolves key from variable', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'user_{{ user_id }}',
            'value' => 'user_data',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', ['user_id' => '123']);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('user_123'))->toBe('user_data');
    });
});

// =============================================================================
// Get Operation Tests
// =============================================================================

describe('get operation', function () {
    it('retrieves existing value', function () {
        Cache::put('existing_key', 'existing_value', 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'get',
            'key' => 'existing_key',
            'store_as' => 'cached_value',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('cached_value'))->toBe('existing_value');
    });

    it('returns null for non-existing key', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'get',
            'key' => 'non_existing_key',
            'store_as' => 'cached_value',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('cached_value'))->toBeNull();
    });

    it('retrieves array value', function () {
        Cache::put('array_key', ['a', 'b', 'c'], 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'get',
            'key' => 'array_key',
            'store_as' => 'cached_array',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('cached_array'))->toBe(['a', 'b', 'c']);
    });

    it('retrieves object value', function () {
        Cache::put('object_key', ['name' => 'Alice', 'age' => 30], 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'get',
            'key' => 'object_key',
            'store_as' => 'cached_object',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('cached_object'))->toBe(['name' => 'Alice', 'age' => 30]);
    });
});

// =============================================================================
// Forget Operation Tests
// =============================================================================

describe('forget operation', function () {
    it('deletes existing key', function () {
        Cache::put('to_delete', 'value', 3600);
        expect(Cache::has('to_delete'))->toBeTrue();

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'forget',
            'key' => 'to_delete',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::has('to_delete'))->toBeFalse();
    });

    it('returns false for non-existing key with array driver', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'forget',
            'key' => 'non_existing',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // array driver returns false when key doesn't exist
        expect($result->get('result'))->toBeFalse();
    });
});

// =============================================================================
// Has Operation Tests
// =============================================================================

describe('has operation', function () {
    it('returns true for existing key', function () {
        Cache::put('check_key', 'value', 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'has',
            'key' => 'check_key',
            'store_as' => 'exists',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('exists'))->toBeTrue();
    });

    it('returns false for non-existing key', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'has',
            'key' => 'missing_key',
            'store_as' => 'exists',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('exists'))->toBeFalse();
    });
});

// =============================================================================
// Increment Operation Tests
// =============================================================================

describe('increment operation', function () {
    it('increments existing value by 1', function () {
        Cache::put('counter', 10, 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'increment',
            'key' => 'counter',
            'amount' => 1,
            'store_as' => 'new_value',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('new_value'))->toBe(11);
        expect(Cache::get('counter'))->toBe(11);
    });

    it('increments by custom amount', function () {
        Cache::put('score', 100, 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'increment',
            'key' => 'score',
            'amount' => 5,
            'store_as' => 'new_score',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('new_score'))->toBe(105);
    });

    it('creates key with value when not existing', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'increment',
            'key' => 'new_counter',
            'amount' => 1,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(1);
    });
});

// =============================================================================
// Decrement Operation Tests
// =============================================================================

describe('decrement operation', function () {
    it('decrements existing value by 1', function () {
        Cache::put('counter', 10, 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'decrement',
            'key' => 'counter',
            'amount' => 1,
            'store_as' => 'new_value',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('new_value'))->toBe(9);
        expect(Cache::get('counter'))->toBe(9);
    });

    it('decrements by custom amount', function () {
        Cache::put('balance', 100, 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'decrement',
            'key' => 'balance',
            'amount' => 25,
            'store_as' => 'new_balance',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('new_balance'))->toBe(75);
    });

    it('can go negative', function () {
        Cache::put('counter', 5, 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'decrement',
            'key' => 'counter',
            'amount' => 10,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(-5);
    });
});

// =============================================================================
// Cache Store Selection Tests
// =============================================================================

describe('cache store selection', function () {
    it('uses default store when not specified', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'default_store_key',
            'value' => 'value',
            'ttl' => 3600,
            'store' => '',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('default_store_key'))->toBe('value');
    });

    it('uses array store when specified', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'array_store_key',
            'value' => 'array_value',
            'ttl' => 3600,
            'store' => 'array',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::store('array')->get('array_store_key'))->toBe('array_value');
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs put operation', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'log_test_key',
            'value' => 'value',
            'ttl' => 3600,
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $message = array_values($infoLogs)[0]['message'];
        expect($message)->toContain('Cache');
        expect($message)->toContain('put');
        expect($message)->toContain('log_test_key');
    });

    it('logs get operation', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'get',
            'key' => 'get_key',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $message = array_values($infoLogs)[0]['message'];
        expect($message)->toContain('get');
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default store_as when not specified', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'test_key',
            'value' => 'test_value',
            'ttl' => 3600,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('cache_result'))->toBeTrue();
    });

    it('uses default ttl when not specified', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'put',
            'key' => 'default_ttl_key',
            'value' => 'value',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeTrue();
        expect(Cache::get('default_ttl_key'))->toBe('value');
    });

    it('uses default amount of 1 for increment', function () {
        Cache::put('inc_test', 10, 3600);

        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'increment',
            'key' => 'inc_test',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(11);
    });
});

// =============================================================================
// Unknown Operation Test
// =============================================================================

describe('unknown operation', function () {
    it('returns null for unknown operation', function () {
        $brick = $this->registry->resolve(CacheAction::class, [
            'operation' => 'unknown',
            'key' => 'test_key',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeNull();
    });
});
