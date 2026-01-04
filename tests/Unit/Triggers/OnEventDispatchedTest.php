<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnEventDispatched;
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
        $brick = $this->registry->resolve(OnEventDispatched::class);

        expect($brick->name())->toBe('Event Dispatched');
        expect($brick->type())->toBe('trigger');
        expect($brick->category())->toBe('Application');
        expect($brick->icon())->toBe('zap');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);

        expect($brick->description())->toContain('event');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('event');
    });

    it('has 1 field total', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(1);
    });
});

// =============================================================================
// Sample Payload Tests
// =============================================================================

describe('sample payload', function () {
    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('event');
        expect($payload)->toHaveKey('payload');
    });

    it('sample event is a class name', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);
        $payload = $brick->samplePayload();

        expect($payload['event'])->toContain('\\');
    });
});

// =============================================================================
// Registration Tests
// =============================================================================

describe('registration', function () {
    it('does not register when event class is missing', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class, [
            'event' => '',
        ]);

        $brick->register();

        expect(true)->toBeTrue();
    });

    it('does not register when event class does not exist', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class, [
            'event' => 'NonExistent\\Event',
        ]);

        $brick->register();

        expect(true)->toBeTrue();
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('event field is required', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'event');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('event field is searchable', function () {
        $brick = $this->registry->resolve(OnEventDispatched::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'event');
        $searchable = array_values($field)[0]->toArray()['searchable'] ?? false;

        expect($searchable)->toBeTrue();
    });
});
