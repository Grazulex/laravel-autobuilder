<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;

it('can create a flow', function () {
    $flow = Flow::create([
        'name' => 'Test Flow',
        'description' => 'A test flow',
        'nodes' => [],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->id)->not->toBeEmpty();
    expect($flow->name)->toBe('Test Flow');
    expect($flow->active)->toBeFalse();
});

it('can activate and deactivate a flow', function () {
    $flow = Flow::create([
        'name' => 'Test Flow',
        'nodes' => [],
        'edges' => [],
        'active' => false,
    ]);

    expect($flow->active)->toBeFalse();

    $flow->activate();
    expect($flow->fresh()->active)->toBeTrue();

    $flow->deactivate();
    expect($flow->fresh()->active)->toBeFalse();
});

it('can duplicate a flow', function () {
    $flow = Flow::create([
        'name' => 'Original Flow',
        'description' => 'Original description',
        'nodes' => [['id' => 'node-1']],
        'edges' => [],
        'active' => true,
    ]);

    $clone = $flow->duplicate();

    expect($clone->id)->not->toBe($flow->id);
    expect($clone->name)->toBe('Original Flow (Copy)');
    expect($clone->nodes)->toBe($flow->nodes);
    expect($clone->active)->toBeFalse();
});

it('can export a flow', function () {
    $flow = Flow::create([
        'name' => 'Export Test',
        'description' => 'Testing export',
        'nodes' => [['id' => 'node-1']],
        'edges' => [['source' => 'node-1', 'target' => 'node-2']],
    ]);

    $export = $flow->export();

    expect($export)->toHaveKeys(['name', 'description', 'nodes', 'edges', 'version', 'exported_at']);
    expect($export['name'])->toBe('Export Test');
    expect($export['version'])->toBe('1.0');
});

it('can import a flow', function () {
    $data = [
        'name' => 'Imported Flow',
        'description' => 'Imported from export',
        'nodes' => [['id' => 'node-1']],
        'edges' => [],
    ];

    $flow = Flow::import($data);

    expect($flow->name)->toBe('Imported Flow');
    expect($flow->active)->toBeFalse();
});

it('can scope to active flows', function () {
    Flow::create(['name' => 'Active Flow', 'nodes' => [], 'edges' => [], 'active' => true]);
    Flow::create(['name' => 'Inactive Flow', 'nodes' => [], 'edges' => [], 'active' => false]);

    expect(Flow::active()->count())->toBe(1);
    expect(Flow::active()->first()->name)->toBe('Active Flow');
});
