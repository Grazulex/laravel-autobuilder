<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;

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
