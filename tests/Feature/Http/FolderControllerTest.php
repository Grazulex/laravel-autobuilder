<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\Folder;

beforeEach(function () {
    $this->withoutMiddleware();
});

describe('index', function () {
    it('returns all folders with their flow counts', function () {
        $folder = Folder::factory()->create(['name' => 'Billing']);
        Folder::factory()->childOf($folder)->create(['name' => 'Invoices']);
        Flow::factory()->count(2)->create(['folder_id' => $folder->id]);
        Flow::factory()->create();

        $response = $this->getJson('/autobuilder/api/folders');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'parent_id', 'flows_count'],
            ],
            'meta' => ['uncategorized_count'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Billing');
        $response->assertJsonPath('data.0.flows_count', 2);
        $response->assertJsonPath('data.1.parent_id', $folder->id);
        $response->assertJsonPath('meta.uncategorized_count', 1);
    });
});

describe('store', function () {
    it('creates a root folder', function () {
        $response = $this->postJson('/autobuilder/api/folders', ['name' => 'Marketing']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Marketing');
        $response->assertJsonPath('data.parent_id', null);

        expect(Folder::where('name', 'Marketing')->exists())->toBeTrue();
    });

    it('creates a nested folder', function () {
        $parent = Folder::factory()->create();

        $response = $this->postJson('/autobuilder/api/folders', [
            'name' => 'Campaigns',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.parent_id', $parent->id);
    });

    it('requires a name', function () {
        $response = $this->postJson('/autobuilder/api/folders', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    });

    it('rejects an unknown parent folder', function () {
        $response = $this->postJson('/autobuilder/api/folders', [
            'name' => 'Orphan',
            'parent_id' => 'does-not-exist',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['parent_id']);
    });

    it('rejects duplicate names at the same level', function () {
        Folder::factory()->create(['name' => 'Marketing']);

        $response = $this->postJson('/autobuilder/api/folders', ['name' => 'Marketing']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    });

    it('allows the same name under different parents', function () {
        $parent = Folder::factory()->create(['name' => 'Marketing']);

        $response = $this->postJson('/autobuilder/api/folders', [
            'name' => 'Marketing',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201);
    });
});

describe('update', function () {
    it('renames a folder', function () {
        $folder = Folder::factory()->create(['name' => 'Old name']);

        $response = $this->putJson("/autobuilder/api/folders/{$folder->id}", ['name' => 'New name']);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'New name');
        expect($folder->fresh()->name)->toBe('New name');
    });

    it('keeps the parent when only renaming', function () {
        $parent = Folder::factory()->create();
        $folder = Folder::factory()->childOf($parent)->create();

        $this->patchJson("/autobuilder/api/folders/{$folder->id}", ['name' => 'Renamed'])->assertOk();

        expect($folder->fresh()->parent_id)->toBe($parent->id);
    });

    it('moves a folder under another folder', function () {
        $parent = Folder::factory()->create();
        $folder = Folder::factory()->create();

        $response = $this->putJson("/autobuilder/api/folders/{$folder->id}", ['parent_id' => $parent->id]);

        $response->assertOk();
        expect($folder->fresh()->parent_id)->toBe($parent->id);
    });

    it('moves a folder back to the root', function () {
        $parent = Folder::factory()->create();
        $folder = Folder::factory()->childOf($parent)->create();

        $response = $this->putJson("/autobuilder/api/folders/{$folder->id}", ['parent_id' => null]);

        $response->assertOk();
        expect($folder->fresh()->parent_id)->toBeNull();
    });

    it('refuses to move a folder into itself', function () {
        $folder = Folder::factory()->create();

        $response = $this->putJson("/autobuilder/api/folders/{$folder->id}", ['parent_id' => $folder->id]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['parent_id']);
    });

    it('refuses to move a folder into one of its sub-folders', function () {
        $root = Folder::factory()->create();
        $child = Folder::factory()->childOf($root)->create();
        $grandchild = Folder::factory()->childOf($child)->create();

        $response = $this->putJson("/autobuilder/api/folders/{$root->id}", ['parent_id' => $grandchild->id]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['parent_id']);
        expect($root->fresh()->parent_id)->toBeNull();
    });

    it('rejects a name already used by a sibling', function () {
        Folder::factory()->create(['name' => 'Taken']);
        $folder = Folder::factory()->create(['name' => 'Free']);

        $response = $this->putJson("/autobuilder/api/folders/{$folder->id}", ['name' => 'Taken']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    });

    it('accepts the current name of the folder', function () {
        $folder = Folder::factory()->create(['name' => 'Same']);

        $this->putJson("/autobuilder/api/folders/{$folder->id}", ['name' => 'Same'])->assertOk();
    });

    it('returns 404 for an unknown folder', function () {
        $this->putJson('/autobuilder/api/folders/unknown', ['name' => 'Nope'])->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes a folder and keeps its flows as uncategorized', function () {
        $folder = Folder::factory()->create();
        $flow = Flow::factory()->create(['folder_id' => $folder->id]);

        $response = $this->deleteJson("/autobuilder/api/folders/{$folder->id}");

        $response->assertOk();
        expect(Folder::find($folder->id))->toBeNull();
        expect($flow->fresh())->not->toBeNull();
        expect($flow->fresh()->folder_id)->toBeNull();
    });

    it('moves flows and sub-folders to the parent folder', function () {
        $parent = Folder::factory()->create();
        $folder = Folder::factory()->childOf($parent)->create();
        $child = Folder::factory()->childOf($folder)->create();
        $flow = Flow::factory()->create(['folder_id' => $folder->id]);

        $this->deleteJson("/autobuilder/api/folders/{$folder->id}")->assertOk();

        expect($flow->fresh()->folder_id)->toBe($parent->id);
        expect($child->fresh()->parent_id)->toBe($parent->id);
    });

    it('returns 404 for an unknown folder', function () {
        $this->deleteJson('/autobuilder/api/folders/unknown')->assertNotFound();
    });
});

describe('assign', function () {
    it('assigns a flow to a folder', function () {
        $folder = Folder::factory()->create();
        $flow = Flow::factory()->create();

        $response = $this->putJson("/autobuilder/api/flows/{$flow->id}/folder", ['folder_id' => $folder->id]);

        $response->assertOk();
        $response->assertJsonPath('data.folder_id', $folder->id);
        $response->assertJsonPath('data.folder.name', $folder->name);
        expect($flow->fresh()->folder_id)->toBe($folder->id);
    });

    it('removes a flow from its folder', function () {
        $folder = Folder::factory()->create();
        $flow = Flow::factory()->create(['folder_id' => $folder->id]);

        $response = $this->putJson("/autobuilder/api/flows/{$flow->id}/folder", ['folder_id' => null]);

        $response->assertOk();
        $response->assertJsonPath('data.folder_id', null);
        expect($flow->fresh()->folder_id)->toBeNull();
    });

    it('requires the folder_id key', function () {
        $flow = Flow::factory()->create();

        $response = $this->putJson("/autobuilder/api/flows/{$flow->id}/folder", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['folder_id']);
    });

    it('rejects an unknown folder', function () {
        $flow = Flow::factory()->create();

        $response = $this->putJson("/autobuilder/api/flows/{$flow->id}/folder", ['folder_id' => 'unknown']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['folder_id']);
    });
});

describe('flows api', function () {
    it('filters flows by folder in index', function () {
        $folder = Folder::factory()->create();
        Flow::factory()->create(['name' => 'In Folder', 'folder_id' => $folder->id]);
        Flow::factory()->create(['name' => 'No Folder']);

        $response = $this->getJson("/autobuilder/api/flows?folder={$folder->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'In Folder');
        $response->assertJsonPath('data.0.folder.id', $folder->id);
    });

    it('lists uncategorized flows in index', function () {
        $folder = Folder::factory()->create();
        Flow::factory()->create(['name' => 'In Folder', 'folder_id' => $folder->id]);
        Flow::factory()->create(['name' => 'No Folder']);

        $response = $this->getJson('/autobuilder/api/flows?folder=uncategorized');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'No Folder');
        $response->assertJsonPath('data.0.folder_id', null);
    });

    it('still returns every flow without a folder filter', function () {
        $folder = Folder::factory()->create();
        Flow::factory()->create(['folder_id' => $folder->id]);
        Flow::factory()->create();

        $this->getJson('/autobuilder/api/flows')->assertOk()->assertJsonCount(2, 'data');
    });

    it('creates a flow inside a folder', function () {
        $folder = Folder::factory()->create();

        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'Foldered Flow',
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.folder_id', $folder->id);
    });

    it('creates a flow without a folder by default', function () {
        $response = $this->postJson('/autobuilder/api/flows', ['name' => 'Plain Flow']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.folder_id', null);
    });

    it('rejects an unknown folder when creating a flow', function () {
        $response = $this->postJson('/autobuilder/api/flows', [
            'name' => 'Broken Flow',
            'folder_id' => 'unknown',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['folder_id']);
    });
});
