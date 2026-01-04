<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\Wait;
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
        $brick = $this->registry->resolve(Wait::class);

        expect($brick->name())->toBe('Wait');
        expect($brick->category())->toBe('Flow Control');
        expect($brick->icon())->toBe('clock');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('wait_type');
        expect($fieldNames)->toContain('duration');
        expect($fieldNames)->toContain('duration_unit');
        expect($fieldNames)->toContain('until_time');
        expect($fieldNames)->toContain('timezone');
        expect($fieldNames)->toContain('async');
        expect($fieldNames)->toContain('pause_reason');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(Wait::class);

        expect($brick->description())->toContain('Pauses');
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default wait_type of duration', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'wait_type');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('duration');
    });

    it('uses default async of true', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'async');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });

    it('uses default timezone of UTC', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'timezone');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('UTC');
    });

    it('uses default duration of 5', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'duration');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(5);
    });

    it('uses default duration_unit of seconds', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'duration_unit');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('seconds');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('wait_type has correct options', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'wait_type');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_keys($options))->toContain('duration');
        expect(array_keys($options))->toContain('until_time');
        expect(array_keys($options))->toContain('pause');
    });

    it('duration_unit has correct options', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'duration_unit');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_keys($options))->toContain('seconds');
        expect(array_keys($options))->toContain('minutes');
        expect(array_keys($options))->toContain('hours');
        expect(array_keys($options))->toContain('days');
    });

    it('has 7 fields total', function () {
        $brick = $this->registry->resolve(Wait::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(7);
    });
});
