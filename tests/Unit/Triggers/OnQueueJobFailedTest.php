<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnQueueJobFailed;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);

        expect($brick->name())->toBe('Queue Job Failed');
        expect($brick->type())->toBe('trigger');
        expect($brick->category())->toBe('Application');
        expect($brick->icon())->toBe('alert-triangle');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);

        expect($brick->description())->toContain('job');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('job');
        expect($fieldNames)->toContain('queue');
    });

    it('has 2 fields total', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });
});

// =============================================================================
// Sample Payload Tests
// =============================================================================

describe('sample payload', function () {
    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('job');
        expect($payload)->toHaveKey('queue');
        expect($payload)->toHaveKey('connection');
        expect($payload)->toHaveKey('exception');
        expect($payload)->toHaveKey('payload');
    });

    it('sample exception has required fields', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $payload = $brick->samplePayload();

        expect($payload['exception'])->toHaveKey('message');
        expect($payload['exception'])->toHaveKey('code');
        expect($payload['exception'])->toHaveKey('file');
        expect($payload['exception'])->toHaveKey('line');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('job field is searchable', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'job');
        $searchable = array_values($field)[0]->toArray()['searchable'] ?? false;

        expect($searchable)->toBeTrue();
    });

    it('job field is optional', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'job');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeFalse();
    });

    it('queue field is optional', function () {
        $brick = $this->registry->resolve(OnQueueJobFailed::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'queue');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeFalse();
    });
});
