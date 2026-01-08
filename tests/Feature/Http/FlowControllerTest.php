<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;

beforeEach(function () {
    $this->withoutMiddleware();
});

// =============================================================================
// Index Tests
// =============================================================================

describe('index', function () {
    it('returns paginated flows', function () {
        for ($i = 0; $i < 3; $i++) {
            Flow::create(['name' => "Flow {$i}", 'nodes' => [], 'edges' => []]);
        }

        $response = $this->getJson('/autobuilder/api/flows');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'active'],
            ],
            'links',
            'meta',
        ]);
    });
});

// =============================================================================
// Store Tests
// =============================================================================

describe('store', function () {
    it('creates a new flow', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'New Test Flow',
            'description' => 'A test description',
            'nodes' => [],
            'edges' => [],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Test Flow');

        $this->assertDatabaseHas('autobuilder_flows', [
            'name' => 'New Test Flow',
        ]);
    });

    it('validates required name', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'description' => 'Missing name',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('creates flow with default inactive status', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'Inactive by Default',
            'nodes' => [],
            'edges' => [],
        ]);

        $response->assertStatus(201);
        expect($response->json('data.active'))->toBeFalse();
    });

    it('validates node structure requires data and position', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'Invalid Nodes',
            'nodes' => [['id' => 'node-1', 'type' => 'trigger']],
            'edges' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nodes.0.data', 'nodes.0.position']);
    });

    it('validates edge structure requires id', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'Invalid Edges',
            'nodes' => [],
            'edges' => [['source' => 'node-1', 'target' => 'node-2']],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['edges.0.id']);
    });

    it('creates flow with complete node structure', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'Complete Flow',
            'nodes' => [
                [
                    'id' => 'node-1',
                    'type' => 'trigger',
                    'data' => ['brick' => 'OnManualTrigger'],
                    'position' => ['x' => 100, 'y' => 100],
                ],
            ],
            'edges' => [],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Complete Flow');
    });
});

// =============================================================================
// Delete Tests
// =============================================================================

describe('destroy', function () {
    it('deletes a flow', function () {
        $flow = Flow::create(['name' => 'To Delete', 'nodes' => [], 'edges' => []]);

        $response = $this->deleteJson("/autobuilder/api/flows/{$flow->id}");

        $response->assertOk();
    });
});

// =============================================================================
// Export/Import Tests
// =============================================================================

describe('export and import', function () {
    it('exports a flow', function () {
        $flow = Flow::create([
            'name' => 'Export Test',
            'nodes' => [['id' => 'node-1']],
            'edges' => [],
        ]);

        $response = $this->getJson("/autobuilder/api/flows/{$flow->id}/export");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['name', 'description', 'nodes', 'edges', 'version', 'exported_at'],
        ]);
    });

    it('imports a flow', function () {
        $response = $this->postJson('/autobuilder/api/flows/import', [
            'name' => 'Imported Flow',
            'description' => 'Imported description',
            'nodes' => [
                [
                    'id' => 'node-1',
                    'type' => 'trigger',
                    'data' => ['brick' => 'OnManualTrigger'],
                    'position' => ['x' => 100, 'y' => 100],
                ],
                [
                    'id' => 'node-2',
                    'type' => 'action',
                    'data' => ['brick' => 'LogMessage'],
                    'position' => ['x' => 300, 'y' => 100],
                ],
            ],
            'edges' => [
                [
                    'id' => 'edge-1',
                    'source' => 'node-1',
                    'target' => 'node-2',
                ],
            ],
        ]);

        $response->assertStatus(201);
        expect($response->json('data.name'))->toBe('Imported Flow');

        $this->assertDatabaseHas('autobuilder_flows', [
            'name' => 'Imported Flow',
        ]);
    });
});
