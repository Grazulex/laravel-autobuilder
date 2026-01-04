<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;

// =============================================================================
// Basic Factory Tests
// =============================================================================

describe('basic factory', function () {
    it('creates a flow run with default attributes', function () {
        $run = FlowRun::factory()->create();

        expect($run)->toBeInstanceOf(FlowRun::class);
        expect($run->id)->not->toBeEmpty();
        expect($run->flow_id)->not->toBeEmpty();
        expect($run->status)->toBe('completed');
        expect($run->payload)->toBeArray();
        expect($run->variables)->toBeArray();
        expect($run->logs)->toBeArray();
        expect($run->started_at)->not->toBeNull();
        expect($run->completed_at)->not->toBeNull();
    });

    it('creates multiple flow runs', function () {
        $runs = FlowRun::factory()->count(3)->create();

        expect($runs)->toHaveCount(3);
    });

    it('creates a flow automatically', function () {
        $run = FlowRun::factory()->create();

        expect($run->flow)->toBeInstanceOf(Flow::class);
    });
});

// =============================================================================
// Status States Tests
// =============================================================================

describe('status states', function () {
    it('creates a running flow run', function () {
        $run = FlowRun::factory()->running()->create();

        expect($run->status)->toBe('running');
        expect($run->completed_at)->toBeNull();
        expect($run->isRunning())->toBeTrue();
    });

    it('creates a completed flow run', function () {
        $run = FlowRun::factory()->completed()->create();

        expect($run->status)->toBe('completed');
        expect($run->isCompleted())->toBeTrue();
    });

    it('creates a failed flow run', function () {
        $run = FlowRun::factory()->failed('Something went wrong')->create();

        expect($run->status)->toBe('failed');
        expect($run->error)->toBe('Something went wrong');
        expect($run->isFailed())->toBeTrue();
    });

    it('creates a paused flow run', function () {
        $run = FlowRun::factory()->paused()->create();

        expect($run->status)->toBe('paused');
        expect($run->completed_at)->toBeNull();
        expect($run->isPaused())->toBeTrue();
    });
});

// =============================================================================
// Data States Tests
// =============================================================================

describe('data states', function () {
    it('creates a run with payload', function () {
        $run = FlowRun::factory()->withPayload(['key' => 'value'])->create();

        expect($run->payload)->toBe(['key' => 'value']);
    });

    it('creates a run with variables', function () {
        $run = FlowRun::factory()->withVariables(['result' => 42])->create();

        expect($run->variables)->toBe(['result' => 42]);
    });

    it('creates a run with logs', function () {
        $run = FlowRun::factory()->withLogs()->create();

        expect($run->logs)->not->toBeEmpty();
        expect($run->logs[0]['level'])->toBe('info');
    });

    it('creates a run with custom logs', function () {
        $customLogs = [['level' => 'error', 'message' => 'Custom error']];
        $run = FlowRun::factory()->withLogs($customLogs)->create();

        expect($run->logs)->toBe($customLogs);
    });
});

// =============================================================================
// Relationship Tests
// =============================================================================

describe('relationships', function () {
    it('creates a run for a specific flow', function () {
        $flow = Flow::factory()->create(['name' => 'Specific Flow']);
        $run = FlowRun::factory()->forFlow($flow)->create();

        expect($run->flow_id)->toBe($flow->id);
        expect($run->flow->name)->toBe('Specific Flow');
    });

    it('creates multiple runs for the same flow', function () {
        $flow = Flow::factory()->create();
        $runs = FlowRun::factory()->count(3)->forFlow($flow)->create();

        expect($runs)->toHaveCount(3);
        expect($runs->pluck('flow_id')->unique()->first())->toBe($flow->id);
    });
});

// =============================================================================
// Chained States Tests
// =============================================================================

describe('chained states', function () {
    it('allows chaining multiple states', function () {
        $flow = Flow::factory()->create();

        $run = FlowRun::factory()
            ->forFlow($flow)
            ->failed('Error occurred')
            ->withPayload(['input' => 'data'])
            ->withLogs()
            ->create();

        expect($run->flow_id)->toBe($flow->id);
        expect($run->status)->toBe('failed');
        expect($run->error)->toBe('Error occurred');
        expect($run->payload)->toBe(['input' => 'data']);
        expect($run->logs)->not->toBeEmpty();
    });
});
