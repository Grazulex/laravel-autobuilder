<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Http\Middleware\AutoBuilderAuth;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Grazulex\AutoBuilder\Tests\Fixtures\Bricks\FlakyAction;

beforeEach(function () {
    $this->withoutMiddleware();
});

// =============================================================================
// Run Tests (Basic validation)
// =============================================================================

describe('run', function () {
    it('rejects run on inactive flow', function () {
        $flow = Flow::create([
            'name' => 'Inactive Flow',
            'active' => false,
            'nodes' => [],
            'edges' => [],
        ]);

        $response = $this->postJson("/autobuilder/api/flows/{$flow->id}/run");

        $response->assertStatus(422);
    });
});

// =============================================================================
// Runs List Tests
// =============================================================================

describe('runs', function () {
    it('returns paginated runs for a flow', function () {
        $flow = Flow::create(['name' => 'Test Flow', 'nodes' => [], 'edges' => []]);

        for ($i = 0; $i < 3; $i++) {
            FlowRun::create([
                'flow_id' => $flow->id,
                'status' => 'completed',
                'payload' => [],
                'variables' => [],
                'logs' => [],
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $response = $this->getJson("/autobuilder/api/flows/{$flow->id}/runs");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'flow_id', 'status', 'started_at'],
            ],
            'links',
            'meta',
        ]);
    });
});

// =============================================================================
// Show Run Tests
// =============================================================================

describe('show run', function () {
    it('returns a single run', function () {
        $flow = Flow::create(['name' => 'Test Flow', 'nodes' => [], 'edges' => []]);
        $run = FlowRun::create([
            'flow_id' => $flow->id,
            'status' => 'completed',
            'payload' => [],
            'variables' => [],
            'logs' => [],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/autobuilder/api/runs/{$run->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'flow_id',
                'status',
                'started_at',
                'completed_at',
            ],
        ]);
    });
});

// =============================================================================
// Replay Tests
// =============================================================================

describe('replay', function () {
    beforeEach(function () {
        // Route model binding is needed here: keep the middleware stack, without the auth check
        $this->withMiddleware();
        $this->withoutMiddleware(AutoBuilderAuth::class);

        // The web middleware group (cookie encryption, CSRF) requires an application key
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'app.cipher' => 'AES-256-CBC',
            'autobuilder.execution.retry_attempts' => 0,
        ]);

        $this->flow = Flow::create([
            'name' => 'Replay Flow',
            'active' => false,
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'brick' => OnManualTrigger::class, 'config' => []],
                ['id' => 'action-1', 'type' => 'action', 'brick' => FlakyAction::class, 'config' => ['fail_times' => 0, 'counter' => 'upstream_attempts']],
                ['id' => 'action-2', 'type' => 'action', 'brick' => FlakyAction::class, 'config' => ['fail_times' => 1, 'counter' => 'downstream_attempts']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
                ['source' => 'action-1', 'target' => 'action-2'],
            ],
        ]);

        $result = app(FlowRunner::class)->run($this->flow, ['order' => 42]);

        expect($result->status)->toBe('failed');

        $this->failedRunId = $result->context->runId;
    });

    it('replays a failed run from the failed node', function () {
        $response = $this->postJson("/autobuilder/api/runs/{$this->failedRunId}/replay");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('data.output.upstream_attempts', 1);
        $response->assertJsonPath('data.output.downstream_attempts', 2);

        expect($response->json('data.run_id'))->not->toBe($this->failedRunId);
        expect(FlowRun::count())->toBe(2);
    });

    it('replays a failed run from a given node', function () {
        $response = $this->postJson("/autobuilder/api/runs/{$this->failedRunId}/replay", [
            'node' => 'action-1',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('data.output.upstream_attempts', 2);
    });

    it('rejects an unknown node', function () {
        $response = $this->postJson("/autobuilder/api/runs/{$this->failedRunId}/replay", [
            'node' => 'missing-node',
        ]);

        $response->assertStatus(422);
        expect(FlowRun::count())->toBe(1);
    });

    it('rejects a run that did not fail', function () {
        $run = FlowRun::create([
            'flow_id' => $this->flow->id,
            'status' => 'completed',
            'payload' => [],
            'variables' => [],
            'logs' => [],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->postJson("/autobuilder/api/runs/{$run->id}/replay")->assertStatus(422);
    });

    it('validates the node parameter', function () {
        $this->postJson("/autobuilder/api/runs/{$this->failedRunId}/replay", ['node' => ['a']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('node');
    });
});

// =============================================================================
// Logs Tests
// =============================================================================

describe('logs', function () {
    it('returns logs for a run', function () {
        $flow = Flow::create(['name' => 'Test Flow', 'nodes' => [], 'edges' => []]);
        $run = FlowRun::create([
            'flow_id' => $flow->id,
            'status' => 'completed',
            'payload' => [],
            'variables' => [],
            'logs' => [
                ['level' => 'info', 'message' => 'Flow started'],
                ['level' => 'info', 'message' => 'Flow completed'],
            ],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/autobuilder/api/runs/{$run->id}/logs");

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'run_id',
            'status',
        ]);
    });
});
