<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnLogin;
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
        $brick = $this->registry->resolve(OnLogin::class);

        expect($brick->name())->toBe('User Login');
        expect($brick->type())->toBe('trigger');
        expect($brick->category())->toBe('Authentication');
        expect($brick->icon())->toBe('log-in');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(OnLogin::class);

        expect($brick->description())->toContain('logs in');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(OnLogin::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('guard');
        expect($fieldNames)->toContain('user_type');
    });

    it('has 2 fields total', function () {
        $brick = $this->registry->resolve(OnLogin::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });
});

// =============================================================================
// Sample Payload Tests
// =============================================================================

describe('sample payload', function () {
    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnLogin::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('user');
        expect($payload)->toHaveKey('user_id');
        expect($payload)->toHaveKey('user_class');
        expect($payload)->toHaveKey('guard');
        expect($payload)->toHaveKey('remember');
        expect($payload)->toHaveKey('ip_address');
        expect($payload)->toHaveKey('user_agent');
        expect($payload)->toHaveKey('logged_in_at');
    });

    it('returns sample user with expected structure', function () {
        $brick = $this->registry->resolve(OnLogin::class);
        $payload = $brick->samplePayload();

        expect($payload['user'])->toHaveKey('id');
        expect($payload['user'])->toHaveKey('name');
        expect($payload['user'])->toHaveKey('email');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('guard field has correct options', function () {
        $brick = $this->registry->resolve(OnLogin::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'guard');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_keys($options))->toContain('');
        expect(array_keys($options))->toContain('web');
        expect(array_keys($options))->toContain('api');
    });

    it('guard field has default of empty string', function () {
        $brick = $this->registry->resolve(OnLogin::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'guard');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('');
    });
});
