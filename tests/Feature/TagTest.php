<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\Tag;

it('can create a tag with auto slug', function () {
    $tag = Tag::create(['name' => 'My Tag']);

    expect($tag->id)->not->toBeEmpty();
    expect($tag->name)->toBe('My Tag');
    expect($tag->slug)->toBe('my-tag');
});

it('can attach a tag to a flow', function () {
    $flow = Flow::factory()->create();
    $tag = Tag::factory()->create();

    $flow->tags()->attach($tag->id);

    expect($flow->tags()->count())->toBe(1);
    expect($flow->tags()->first()->id)->toBe($tag->id);
});

it('can detach a tag from a flow', function () {
    $flow = Flow::factory()->create();
    $tag = Tag::factory()->create();

    $flow->tags()->attach($tag->id);
    $flow->tags()->detach($tag->id);

    expect($flow->tags()->count())->toBe(0);
});

it('can filter flows by tag slug', function () {
    $tag = Tag::factory()->create(['name' => 'critical', 'slug' => 'critical']);
    $taggedFlow = Flow::factory()->create();
    $untaggedFlow = Flow::factory()->create();

    $taggedFlow->tags()->attach($tag->id);

    $results = Flow::withTag('critical')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($taggedFlow->id);
});

it('can filter flows by tag name', function () {
    $tag = Tag::factory()->create(['name' => 'onboarding', 'slug' => 'onboarding']);
    $taggedFlow = Flow::factory()->create();

    $taggedFlow->tags()->attach($tag->id);

    $results = Flow::withTag('onboarding')->get();

    expect($results)->toHaveCount(1);
});

it('a flow can have multiple tags', function () {
    $flow = Flow::factory()->create();
    $tags = Tag::factory()->count(3)->create();

    $flow->tags()->attach($tags->pluck('id'));

    expect($flow->tags()->count())->toBe(3);
});

it('deleting a tag removes pivot entries', function () {
    $flow = Flow::factory()->create();
    $tag = Tag::factory()->create();

    $flow->tags()->attach($tag->id);
    $tag->delete();

    expect($flow->fresh()->tags()->count())->toBe(0);
});

it('tag slugs are unique', function () {
    Tag::create(['name' => 'Notifications']);

    expect(fn () => Tag::create(['name' => 'Notifications']))->toThrow(\Illuminate\Database\QueryException::class);
});
