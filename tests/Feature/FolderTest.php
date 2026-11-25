<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\Folder;

it('can create a folder', function () {
    $folder = Folder::create(['name' => 'Billing']);

    expect($folder->id)->not->toBeEmpty();
    expect($folder->name)->toBe('Billing');
    expect($folder->parent_id)->toBeNull();
});

it('can assign a flow to a folder', function () {
    $folder = Folder::factory()->create();
    $flow = Flow::factory()->create(['folder_id' => $folder->id]);

    expect($flow->folder->id)->toBe($folder->id);
    expect($folder->flows()->count())->toBe(1);
});

it('keeps flows without a folder accessible', function () {
    $folder = Folder::factory()->create();
    Flow::factory()->create(['folder_id' => $folder->id]);
    $uncategorized = Flow::factory()->create();

    expect($uncategorized->folder)->toBeNull();
    expect(Flow::count())->toBe(2);

    $results = Flow::inFolder(null)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($uncategorized->id);
});

it('can filter flows by folder', function () {
    $folder = Folder::factory()->create();
    $inFolder = Flow::factory()->create(['folder_id' => $folder->id]);
    Flow::factory()->create();

    $results = Flow::inFolder($folder->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($inFolder->id);
});

it('supports nested folders', function () {
    $root = Folder::factory()->create();
    $child = Folder::factory()->childOf($root)->create();
    $grandchild = Folder::factory()->childOf($child)->create();

    expect($child->parent->id)->toBe($root->id);
    expect($root->children()->count())->toBe(1);
    expect(Folder::roots()->count())->toBe(1);
    expect($root->descendantIds())->toEqualCanonicalizing([$child->id, $grandchild->id]);
    expect($grandchild->descendantIds())->toBe([]);
});

it('knows where a folder can be moved', function () {
    $root = Folder::factory()->create();
    $child = Folder::factory()->childOf($root)->create();
    $other = Folder::factory()->create();

    expect($root->canMoveTo(null))->toBeTrue();
    expect($root->canMoveTo($other->id))->toBeTrue();
    expect($root->canMoveTo($root->id))->toBeFalse();
    expect($root->canMoveTo($child->id))->toBeFalse();
});

it('moves flows and sub-folders up when a folder is deleted', function () {
    $root = Folder::factory()->create();
    $folder = Folder::factory()->childOf($root)->create();
    $child = Folder::factory()->childOf($folder)->create();
    $flow = Flow::factory()->create(['folder_id' => $folder->id]);

    $folder->delete();

    expect($flow->fresh()->folder_id)->toBe($root->id);
    expect($child->fresh()->parent_id)->toBe($root->id);
});

it('moves soft deleted flows too when a folder is deleted', function () {
    $folder = Folder::factory()->create();
    $flow = Flow::factory()->create(['folder_id' => $folder->id]);
    $flow->delete();

    $folder->delete();

    expect(Flow::withTrashed()->find($flow->id)->folder_id)->toBeNull();
});

it('keeps the folder when a flow is duplicated', function () {
    $folder = Folder::factory()->create();
    $flow = Flow::factory()->create(['folder_id' => $folder->id]);

    expect($flow->duplicate()->folder_id)->toBe($folder->id);
});
