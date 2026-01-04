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
            'nodes' => [['id' => 'node-1']],
            'edges' => [['source' => 'node-1', 'target' => 'node-2']],
        ]);

        $response->assertStatus(201);
        expect($response->json('data.name'))->toBe('Imported Flow');

        $this->assertDatabaseHas('autobuilder_flows', [
            'name' => 'Imported Flow',
        ]);
    });
});
