<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SetVariable;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldEquals;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelCreated;

beforeEach(function () {
    $this->withoutMiddleware();
});

// =============================================================================
// Index Tests
// =============================================================================

describe('index', function () {
    it('returns all bricks grouped by type', function () {
        $response = $this->getJson('/autobuilder/api/bricks');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'triggers',
                'conditions',
                'actions',
            ],
            'meta' => [
                'triggers_count',
                'conditions_count',
                'actions_count',
            ],
        ]);
    });

    it('can filter by type triggers', function () {
        $response = $this->getJson('/autobuilder/api/bricks?type=triggers');

        $response->assertOk();
        expect($response->json('data'))->toHaveKey('triggers');
        expect($response->json('data'))->not->toHaveKey('conditions');
        expect($response->json('data'))->not->toHaveKey('actions');
    });

    it('can filter by type conditions', function () {
        $response = $this->getJson('/autobuilder/api/bricks?type=conditions');

        $response->assertOk();
        expect($response->json('data'))->toHaveKey('conditions');
        expect($response->json('data'))->not->toHaveKey('triggers');
    });

    it('can filter by type actions', function () {
        $response = $this->getJson('/autobuilder/api/bricks?type=actions');

        $response->assertOk();
        expect($response->json('data'))->toHaveKey('actions');
        expect($response->json('data'))->not->toHaveKey('triggers');
    });

    it('includes meta counts', function () {
        $response = $this->getJson('/autobuilder/api/bricks');

        $response->assertOk();
        expect($response->json('meta.triggers_count'))->toBeGreaterThan(0);
        expect($response->json('meta.conditions_count'))->toBeGreaterThan(0);
        expect($response->json('meta.actions_count'))->toBeGreaterThan(0);
    });
});

// =============================================================================
// Schema Tests
// =============================================================================

describe('schema', function () {
    it('returns schema for a trigger', function () {
        $brickClass = urlencode(OnModelCreated::class);

        $response = $this->getJson("/autobuilder/api/bricks/{$brickClass}/schema");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'class',
                'name',
                'type',
                'category',
                'icon',
                'description',
                'fields',
            ],
        ]);
    });

    it('returns schema for a condition', function () {
        $brickClass = urlencode(FieldEquals::class);

        $response = $this->getJson("/autobuilder/api/bricks/{$brickClass}/schema");

        $response->assertOk();
        expect($response->json('data.type'))->toBe('condition');
    });

    it('returns schema for an action', function () {
        $brickClass = urlencode(SetVariable::class);

        $response = $this->getJson("/autobuilder/api/bricks/{$brickClass}/schema");

        $response->assertOk();
        expect($response->json('data.type'))->toBe('action');
    });

    it('returns 404 for non-existent brick', function () {
        $brickClass = urlencode('NonExistent\\Brick');

        $response = $this->getJson("/autobuilder/api/bricks/{$brickClass}/schema");

        $response->assertNotFound();
    });
});
