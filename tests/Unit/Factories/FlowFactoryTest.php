<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;

// =============================================================================
// Basic Factory Tests
// =============================================================================

describe('basic factory', function () {
    it('creates a flow with default attributes', function () {
        $flow = Flow::factory()->create();

        expect($flow)->toBeInstanceOf(Flow::class);
        expect($flow->id)->not->toBeEmpty();
        expect($flow->name)->not->toBeEmpty();
        expect($flow->nodes)->toBeArray();
        expect($flow->edges)->toBeArray();
        expect($flow->active)->toBeFalse();
        expect($flow->sync)->toBeFalse();
    });

    it('creates multiple flows', function () {
        $flows = Flow::factory()->count(3)->create();

        expect($flows)->toHaveCount(3);
        expect($flows->pluck('id')->unique())->toHaveCount(3);
    });

    it('makes a flow without persisting', function () {
        $flow = Flow::factory()->make();

        expect($flow->exists)->toBeFalse();
        expect($flow->name)->not->toBeEmpty();
    });
});

// =============================================================================
// State Tests
// =============================================================================

describe('states', function () {
    it('creates an active flow', function () {
        $flow = Flow::factory()->active()->create();

        expect($flow->active)->toBeTrue();
    });

    it('creates an inactive flow', function () {
        $flow = Flow::factory()->inactive()->create();

        expect($flow->active)->toBeFalse();
    });

    it('creates a sync flow', function () {
        $flow = Flow::factory()->sync()->create();

        expect($flow->sync)->toBeTrue();
    });

    it('creates a flow with webhook', function () {
        $flow = Flow::factory()->withWebhook('my-webhook')->create();

        expect($flow->webhook_path)->toBe('my-webhook');
    });

    it('creates a flow with webhook and secret', function () {
        $flow = Flow::factory()->withWebhookSecret('secret123', 'secure-hook')->create();

        expect($flow->webhook_path)->toBe('secure-hook');
        expect($flow->trigger_config['secret'])->toBe('secret123');
    });

    it('creates a flow with nodes', function () {
        $flow = Flow::factory()->withNodes()->create();

        expect($flow->nodes)->not->toBeEmpty();
        expect($flow->nodes[0]['id'])->toBe('trigger-1');
    });

    it('creates a flow with edges', function () {
        $flow = Flow::factory()->withEdges()->create();

        expect($flow->edges)->not->toBeEmpty();
        expect($flow->edges[0]['source'])->toBe('trigger-1');
    });

    it('creates a complete flow with nodes and edges', function () {
        $flow = Flow::factory()->complete()->create();

        expect($flow->nodes)->not->toBeEmpty();
        expect($flow->edges)->not->toBeEmpty();
    });
});

// =============================================================================
// Custom Attributes Tests
// =============================================================================

describe('custom attributes', function () {
    it('allows overriding name', function () {
        $flow = Flow::factory()->create(['name' => 'Custom Name']);

        expect($flow->name)->toBe('Custom Name');
    });

    it('allows overriding description', function () {
        $flow = Flow::factory()->create(['description' => 'Custom description']);

        expect($flow->description)->toBe('Custom description');
    });

    it('allows custom nodes', function () {
        $customNodes = [['id' => 'custom-node', 'type' => 'action']];
        $flow = Flow::factory()->withNodes($customNodes)->create();

        expect($flow->nodes)->toBe($customNodes);
    });
});

// =============================================================================
// Chained States Tests
// =============================================================================

describe('chained states', function () {
    it('allows chaining multiple states', function () {
        $flow = Flow::factory()
            ->active()
            ->sync()
            ->withWebhook('test-hook')
            ->withEdges()
            ->create();

        expect($flow->active)->toBeTrue();
        expect($flow->sync)->toBeTrue();
        expect($flow->webhook_path)->toBe('test-hook');
        expect($flow->nodes)->not->toBeEmpty();
        expect($flow->edges)->not->toBeEmpty();
    });
});
