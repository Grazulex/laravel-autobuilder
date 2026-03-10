<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\Tag;

beforeEach(function () {
    $this->withoutMiddleware();
});

describe('index', function () {
    it('returns all tags', function () {
        Tag::factory()->count(3)->create();

        $response = $this->getJson('/autobuilder/api/tags');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug'],
            ],
        ]);
        $response->assertJsonCount(3, 'data');
    });
});

describe('store', function () {
    it('creates a new tag', function () {
        $response = $this->postJson('/autobuilder/api/tags', ['name' => 'Notifications']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Notifications');
        $response->assertJsonPath('data.slug', 'notifications');

        expect(Tag::where('name', 'Notifications')->exists())->toBeTrue();
    });

    it('rejects duplicate tag names', function () {
        Tag::factory()->create(['name' => 'Existing', 'slug' => 'existing']);

        $response = $this->postJson('/autobuilder/api/tags', ['name' => 'Existing']);

        $response->assertUnprocessable();
    });

    it('requires a name', function () {
        $response = $this->postJson('/autobuilder/api/tags', []);

        $response->assertUnprocessable();
    });
});

describe('attach', function () {
    it('attaches a tag to a flow', function () {
        $flow = Flow::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->postJson("/autobuilder/api/flows/{$flow->id}/tags/{$tag->id}");

        $response->assertOk();
        expect($flow->tags()->where('tag_id', $tag->id)->exists())->toBeTrue();
    });

    it('is idempotent (no duplicate pivot rows)', function () {
        $flow = Flow::factory()->create();
        $tag = Tag::factory()->create();

        $this->postJson("/autobuilder/api/flows/{$flow->id}/tags/{$tag->id}");
        $this->postJson("/autobuilder/api/flows/{$flow->id}/tags/{$tag->id}");

        expect($flow->tags()->count())->toBe(1);
    });
});

describe('detach', function () {
    it('detaches a tag from a flow', function () {
        $flow = Flow::factory()->create();
        $tag = Tag::factory()->create();
        $flow->tags()->attach($tag->id);

        $response = $this->deleteJson("/autobuilder/api/flows/{$flow->id}/tags/{$tag->id}");

        $response->assertOk();
        expect($flow->tags()->count())->toBe(0);
    });
});

describe('filter by tag', function () {
    it('filters flows by tag in index', function () {
        $tag = Tag::factory()->create(['name' => 'critical', 'slug' => 'critical']);
        $taggedFlow = Flow::factory()->create(['name' => 'Tagged Flow']);
        Flow::factory()->create(['name' => 'Other Flow']);

        $taggedFlow->tags()->attach($tag->id);

        $response = $this->getJson('/autobuilder/api/flows?tag=critical');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Tagged Flow');
    });
});
