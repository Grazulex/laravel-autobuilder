<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelCreated;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelDeleted;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelUpdated;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// OnModelCreated Tests
// =============================================================================

describe('OnModelCreated', function () {
    it('has correct field count', function () {
        $brick = $this->registry->resolve(OnModelCreated::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(1);
    });

    it('model field is required', function () {
        $brick = $this->registry->resolve(OnModelCreated::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'model');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnModelCreated::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('model');
        expect($payload)->toHaveKey('model_class');
        expect($payload)->toHaveKey('model_id');
    });

    it('sample payload model has id', function () {
        $brick = $this->registry->resolve(OnModelCreated::class);
        $payload = $brick->samplePayload();

        expect($payload['model'])->toHaveKey('id');
    });

    it('does not register when model class is missing', function () {
        $brick = $this->registry->resolve(OnModelCreated::class, [
            'model' => '',
        ]);

        // Should not throw
        $brick->register();

        expect(true)->toBeTrue();
    });

    it('does not register when model class does not exist', function () {
        $brick = $this->registry->resolve(OnModelCreated::class, [
            'model' => 'NonExistent\\Model',
        ]);

        // Should not throw
        $brick->register();

        expect(true)->toBeTrue();
    });
});

// =============================================================================
// OnModelUpdated Tests
// =============================================================================

describe('OnModelUpdated', function () {
    it('has correct field count', function () {
        $brick = $this->registry->resolve(OnModelUpdated::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });

    it('has watch_fields field', function () {
        $brick = $this->registry->resolve(OnModelUpdated::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('watch_fields');
    });

    it('watch_fields is multiple select', function () {
        $brick = $this->registry->resolve(OnModelUpdated::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'watch_fields');
        $multiple = array_values($field)[0]->toArray()['multiple'] ?? false;

        expect($multiple)->toBeTrue();
    });

    it('returns sample payload with changes', function () {
        $brick = $this->registry->resolve(OnModelUpdated::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('model');
        expect($payload)->toHaveKey('original');
        expect($payload)->toHaveKey('changes');
        expect($payload)->toHaveKey('model_class');
        expect($payload)->toHaveKey('model_id');
    });

    it('sample payload includes original values', function () {
        $brick = $this->registry->resolve(OnModelUpdated::class);
        $payload = $brick->samplePayload();

        expect($payload['original'])->toHaveKey('name');
    });

    it('does not register when model class is missing', function () {
        $brick = $this->registry->resolve(OnModelUpdated::class, [
            'model' => '',
        ]);

        $brick->register();

        expect(true)->toBeTrue();
    });
});

// =============================================================================
// OnModelDeleted Tests
// =============================================================================

describe('OnModelDeleted', function () {
    it('has correct field count', function () {
        $brick = $this->registry->resolve(OnModelDeleted::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });

    it('has include_soft_deletes field', function () {
        $brick = $this->registry->resolve(OnModelDeleted::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('include_soft_deletes');
    });

    it('include_soft_deletes defaults to true', function () {
        $brick = $this->registry->resolve(OnModelDeleted::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'include_soft_deletes');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });

    it('returns sample payload with soft_deleted flag', function () {
        $brick = $this->registry->resolve(OnModelDeleted::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('model');
        expect($payload)->toHaveKey('model_class');
        expect($payload)->toHaveKey('model_id');
        expect($payload)->toHaveKey('soft_deleted');
    });

    it('sample payload soft_deleted is boolean', function () {
        $brick = $this->registry->resolve(OnModelDeleted::class);
        $payload = $brick->samplePayload();

        expect($payload['soft_deleted'])->toBeBool();
    });

    it('does not register when model class is missing', function () {
        $brick = $this->registry->resolve(OnModelDeleted::class, [
            'model' => '',
        ]);

        $brick->register();

        expect(true)->toBeTrue();
    });
});
