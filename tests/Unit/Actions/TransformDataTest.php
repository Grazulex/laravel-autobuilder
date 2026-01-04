<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\TransformData;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(TransformData::class);

        expect($brick->name())->toBe('Transform Data');
        expect($brick->category())->toBe('Data');
        expect($brick->icon())->toBe('shuffle');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(TransformData::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('source');
        expect($fieldNames)->toContain('operation');
        expect($fieldNames)->toContain('field');
        expect($fieldNames)->toContain('value');
        expect($fieldNames)->toContain('amount');
        expect($fieldNames)->toContain('store_as');
    });
});

// =============================================================================
// Null/Empty Source Tests
// =============================================================================

describe('null source handling', function () {
    it('handles null source gracefully', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'nonexistent',
            'operation' => 'pluck',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', []);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeNull();

        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();
    });

    it('handles empty array', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'count',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', ['items' => []]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(0);
    });
});

// =============================================================================
// Pluck Operation Tests
// =============================================================================

describe('pluck operation', function () {
    it('plucks single field from array of objects', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'pluck',
            'field' => 'name',
            'store_as' => 'names',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
                ['name' => 'Charlie', 'age' => 35],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('names'))->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('plucks nested field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'pluck',
            'field' => 'profile.email',
            'store_as' => 'emails',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['profile' => ['email' => 'alice@test.com']],
                ['profile' => ['email' => 'bob@test.com']],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('emails'))->toBe(['alice@test.com', 'bob@test.com']);
    });
});

// =============================================================================
// Filter Operations Tests
// =============================================================================

describe('filter operations', function () {
    it('filters out empty values', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'filter_not_empty',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['apple', '', 'banana', null, 'cherry', 0, false],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['apple', 'banana', 'cherry']);
    });

    it('filters by field value', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'filter_by_field',
            'field' => 'status',
            'value' => 'active',
            'store_as' => 'active_users',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['name' => 'Alice', 'status' => 'active'],
                ['name' => 'Bob', 'status' => 'inactive'],
                ['name' => 'Charlie', 'status' => 'active'],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('active_users'))->toHaveCount(2);
        expect($result->get('active_users')[0]['name'])->toBe('Alice');
        expect($result->get('active_users')[1]['name'])->toBe('Charlie');
    });

    it('filters by field value with variable', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'filter_by_field',
            'field' => 'role',
            'value' => '{{ filter_role }}',
            'store_as' => 'filtered',
        ]);

        $context = new FlowContext('flow-1', [
            'filter_role' => 'admin',
            'users' => [
                ['name' => 'Alice', 'role' => 'admin'],
                ['name' => 'Bob', 'role' => 'user'],
                ['name' => 'Charlie', 'role' => 'admin'],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('filtered'))->toHaveCount(2);
    });
});

// =============================================================================
// Sort Operations Tests
// =============================================================================

describe('sort operations', function () {
    it('sorts ascending', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'numbers',
            'operation' => 'sort_asc',
            'store_as' => 'sorted',
        ]);

        $context = new FlowContext('flow-1', [
            'numbers' => [3, 1, 4, 1, 5, 9, 2, 6],
        ]);
        $result = $brick->handle($context);

        expect($result->get('sorted'))->toBe([1, 1, 2, 3, 4, 5, 6, 9]);
    });

    it('sorts descending', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'numbers',
            'operation' => 'sort_desc',
            'store_as' => 'sorted',
        ]);

        $context = new FlowContext('flow-1', [
            'numbers' => [3, 1, 4, 1, 5],
        ]);
        $result = $brick->handle($context);

        expect($result->get('sorted'))->toBe([5, 4, 3, 1, 1]);
    });

    it('sorts by field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'sort_by_field',
            'field' => 'age',
            'store_as' => 'sorted',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['name' => 'Bob', 'age' => 25],
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Charlie', 'age' => 20],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('sorted')[0]['name'])->toBe('Charlie');
        expect($result->get('sorted')[1]['name'])->toBe('Bob');
        expect($result->get('sorted')[2]['name'])->toBe('Alice');
    });
});

// =============================================================================
// Unique Operation Tests
// =============================================================================

describe('unique operation', function () {
    it('removes duplicate values', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'unique',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['apple', 'banana', 'apple', 'cherry', 'banana'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['apple', 'banana', 'cherry']);
    });

    it('removes duplicates by field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'unique',
            'field' => 'role',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['name' => 'Alice', 'role' => 'admin'],
                ['name' => 'Bob', 'role' => 'user'],
                ['name' => 'Charlie', 'role' => 'admin'],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toHaveCount(2);
    });
});

// =============================================================================
// Array Manipulation Tests
// =============================================================================

describe('array manipulation operations', function () {
    it('flattens nested arrays', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'nested',
            'operation' => 'flatten',
            'store_as' => 'flat',
        ]);

        $context = new FlowContext('flow-1', [
            'nested' => [[1, 2], [3, 4], [5]],
        ]);
        $result = $brick->handle($context);

        expect($result->get('flat'))->toBe([1, 2, 3, 4, 5]);
    });

    it('reverses array order', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'reverse',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['a', 'b', 'c', 'd'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['d', 'c', 'b', 'a']);
    });

    it('gets array keys', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'data',
            'operation' => 'keys',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'data' => ['name' => 'Alice', 'age' => 30, 'role' => 'admin'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['name', 'age', 'role']);
    });

    it('gets array values', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'data',
            'operation' => 'values',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'data' => ['name' => 'Alice', 'age' => 30],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['Alice', 30]);
    });
});

// =============================================================================
// Take/Skip Operations Tests
// =============================================================================

describe('take and skip operations', function () {
    it('takes first N items', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'take',
            'amount' => '3',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe([1, 2, 3]);
    });

    it('skips first N items', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'skip',
            'amount' => '3',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => [1, 2, 3, 4, 5],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe([4, 5]);
    });

    it('uses default amount when not specified', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'take',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => range(1, 20),
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toHaveCount(10);
    });
});

// =============================================================================
// Aggregation Operations Tests
// =============================================================================

describe('aggregation operations', function () {
    it('counts items', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'count',
            'store_as' => 'total',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['a', 'b', 'c', 'd', 'e'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('total'))->toBe(5);
    });

    it('sums numeric values', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'amounts',
            'operation' => 'sum',
            'store_as' => 'total',
        ]);

        $context = new FlowContext('flow-1', [
            'amounts' => [10, 20, 30, 40],
        ]);
        $result = $brick->handle($context);

        expect($result->get('total'))->toBe(100);
    });

    it('sums by field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'orders',
            'operation' => 'sum',
            'field' => 'amount',
            'store_as' => 'total',
        ]);

        $context = new FlowContext('flow-1', [
            'orders' => [
                ['name' => 'Order 1', 'amount' => 100],
                ['name' => 'Order 2', 'amount' => 200],
                ['name' => 'Order 3', 'amount' => 150],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('total'))->toBe(450);
    });

    it('calculates average', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'scores',
            'operation' => 'avg',
            'store_as' => 'average',
        ]);

        $context = new FlowContext('flow-1', [
            'scores' => [10, 20, 30, 40],
        ]);
        $result = $brick->handle($context);

        expect($result->get('average'))->toBe(25);
    });

    it('calculates average by field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'students',
            'operation' => 'avg',
            'field' => 'grade',
            'store_as' => 'average',
        ]);

        $context = new FlowContext('flow-1', [
            'students' => [
                ['name' => 'Alice', 'grade' => 90],
                ['name' => 'Bob', 'grade' => 80],
                ['name' => 'Charlie', 'grade' => 70],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('average'))->toBe(80);
    });

    it('finds minimum value', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'numbers',
            'operation' => 'min',
            'store_as' => 'minimum',
        ]);

        $context = new FlowContext('flow-1', [
            'numbers' => [5, 3, 8, 1, 9],
        ]);
        $result = $brick->handle($context);

        expect($result->get('minimum'))->toBe(1);
    });

    it('finds minimum by field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'products',
            'operation' => 'min',
            'field' => 'price',
            'store_as' => 'lowest_price',
        ]);

        $context = new FlowContext('flow-1', [
            'products' => [
                ['name' => 'A', 'price' => 50],
                ['name' => 'B', 'price' => 30],
                ['name' => 'C', 'price' => 70],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('lowest_price'))->toBe(30);
    });

    it('finds maximum value', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'numbers',
            'operation' => 'max',
            'store_as' => 'maximum',
        ]);

        $context = new FlowContext('flow-1', [
            'numbers' => [5, 3, 8, 1, 9],
        ]);
        $result = $brick->handle($context);

        expect($result->get('maximum'))->toBe(9);
    });

    it('finds maximum by field', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'products',
            'operation' => 'max',
            'field' => 'price',
            'store_as' => 'highest_price',
        ]);

        $context = new FlowContext('flow-1', [
            'products' => [
                ['name' => 'A', 'price' => 50],
                ['name' => 'B', 'price' => 30],
                ['name' => 'C', 'price' => 70],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('highest_price'))->toBe(70);
    });
});

// =============================================================================
// First/Last Operations Tests
// =============================================================================

describe('first and last operations', function () {
    it('gets first item', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'first',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['apple', 'banana', 'cherry'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe('apple');
    });

    it('gets first item from objects', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'first',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['name' => 'Alice']);
    });

    it('gets last item', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'last',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['apple', 'banana', 'cherry'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe('cherry');
    });

    it('returns null for first on empty array', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'first',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => [],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBeNull();
    });
});

// =============================================================================
// Implode Operation Tests
// =============================================================================

describe('implode operation', function () {
    it('joins array to string with default delimiter', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'implode',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['apple', 'banana', 'cherry'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe('apple, banana, cherry');
    });

    it('joins array to string with custom delimiter', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'implode',
            'value' => ' | ',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['one', 'two', 'three'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe('one | two | three');
    });

    it('joins plucked field values', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'users',
            'operation' => 'implode',
            'field' => 'name',
            'value' => ', ',
            'store_as' => 'names',
        ]);

        $context = new FlowContext('flow-1', [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
                ['name' => 'Charlie', 'age' => 35],
            ],
        ]);
        $result = $brick->handle($context);

        expect($result->get('names'))->toBe('Alice, Bob, Charlie');
    });
});

// =============================================================================
// Variable Templating Tests
// =============================================================================

describe('variable templating', function () {
    it('resolves source from variable', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => '{{ data_key }}',
            'operation' => 'count',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'data_key' => 'my_items',
            'my_items' => [1, 2, 3, 4, 5],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(5);
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs successful transformation with count for arrays', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'filter_not_empty',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['a', 'b', 'c'],
        ]);
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $message = array_values($infoLogs)[0]['message'];
        expect($message)->toContain('TransformData');
        expect($message)->toContain('filter_not_empty');
    });

    it('logs scalar results directly', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'numbers',
            'operation' => 'count',
            'store_as' => 'total',
        ]);

        $context = new FlowContext('flow-1', [
            'numbers' => [1, 2, 3],
        ]);
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $message = array_values($infoLogs)[0]['message'];
        expect($message)->toContain('3');
    });
});

// =============================================================================
// Default Operation Test
// =============================================================================

describe('default operation', function () {
    it('returns original data for unknown operation', function () {
        $brick = $this->registry->resolve(TransformData::class, [
            'source' => 'items',
            'operation' => 'unknown_operation',
            'store_as' => 'result',
        ]);

        $context = new FlowContext('flow-1', [
            'items' => ['a', 'b', 'c'],
        ]);
        $result = $brick->handle($context);

        expect($result->get('result'))->toBe(['a', 'b', 'c']);
    });
});
