<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\Exceptions\FlowException;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Tests\Fixtures\Bricks\FlakyAction;

beforeEach(function () {
    config(['autobuilder.execution.retry_attempts' => 0]);

    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
    $this->runner = new FlowRunner($this->registry);

    $this->flow = Flow::create([
        'name' => 'Replay Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
            [
                'id' => 'action-1',
                'type' => 'action',
                'brick' => FlakyAction::class,
                'config' => ['fail_times' => 0, 'counter' => 'upstream_attempts'],
            ],
            [
                'id' => 'action-2',
                'type' => 'action',
                'brick' => FlakyAction::class,
                'config' => ['fail_times' => 1, 'counter' => 'downstream_attempts'],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'action-1'],
            ['source' => 'action-1', 'target' => 'action-2'],
        ],
        'is_active' => true,
    ]);
});

it('stores a failed run with its error and the failed node', function () {
    $result = $this->runner->run($this->flow, ['order' => 42]);

    expect($result->status)->toBe('failed');

    $run = FlowRun::findOrFail($result->context->runId);

    expect($run->status)->toBe('failed');
    expect($run->isFailed())->toBeTrue();
    expect($run->error)->toBe('Flaky failure #1');
    expect($run->failedNodeId())->toBe('action-2');
});

it('replays a failed run from the node that failed', function () {
    $failed = $this->runner->run($this->flow, ['order' => 42]);
    $run = FlowRun::findOrFail($failed->context->runId);

    $result = $this->runner->replay($run);

    expect($result->status)->toBe('completed');
    expect($result->context->runId)->not->toBe($run->id);
    expect($result->context->payload)->toBe(['order' => 42]);
    // The upstream node is not executed again, its output is kept
    expect($result->context->get('upstream_attempts'))->toBe(1);
    expect($result->context->get('downstream_attempts'))->toBe(2);

    expect($result->context->logs[0]['data'])->toBe([
        'replay_of' => $run->id,
        'node_id' => 'action-2',
    ]);

    expect(FlowRun::count())->toBe(2);
    expect(FlowRun::findOrFail($result->context->runId)->status)->toBe('completed');
    expect($run->fresh()->status)->toBe('failed');
});

it('replays a run from an explicit node', function () {
    $failed = $this->runner->run($this->flow);
    $run = FlowRun::findOrFail($failed->context->runId);

    $result = $this->runner->replay($run, 'action-1');

    expect($result->status)->toBe('completed');
    expect($result->context->get('upstream_attempts'))->toBe(2);
    expect($result->context->get('downstream_attempts'))->toBe(2);
});

it('throws when the replay node does not exist', function () {
    $failed = $this->runner->run($this->flow);
    $run = FlowRun::findOrFail($failed->context->runId);

    $this->runner->replay($run, 'missing-node');
})->throws(FlowException::class, 'Node not found: missing-node');

it('throws when the run has no failed node', function () {
    $run = FlowRun::create([
        'flow_id' => $this->flow->id,
        'status' => 'failed',
        'payload' => [],
        'variables' => [],
        'logs' => [['level' => 'error', 'message' => 'Boom', 'data' => []]],
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $this->runner->replay($run);
})->throws(FlowException::class, 'has no failed node to replay from');

it('records the deepest node as the failed node', function () {
    $this->flow->update([
        'nodes' => [
            ...$this->flow->nodes,
            [
                'id' => 'action-3',
                'type' => 'action',
                'brick' => FlakyAction::class,
                'config' => ['fail_times' => 5, 'counter' => 'last_attempts'],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'action-1'],
            ['source' => 'action-1', 'target' => 'action-3'],
        ],
    ]);

    $result = $this->runner->run($this->flow->fresh());

    expect(FlowRun::findOrFail($result->context->runId)->failedNodeId())->toBe('action-3');
});
