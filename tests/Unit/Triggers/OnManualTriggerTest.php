<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
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
        $brick = $this->registry->resolve(OnManualTrigger::class);

        expect($brick->name())->toBe('Manual Trigger');
        expect($brick->type())->toBe('trigger');
        expect($brick->category())->toBe('Manual');
        expect($brick->icon())->toBe('play');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class);

        expect($brick->description())->toContain('manual');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('default_payload');
        expect($fieldNames)->toContain('description');
    });

    it('has 2 fields total', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });
});

// =============================================================================
// Sample Payload Tests
// =============================================================================

describe('sample payload', function () {
    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('triggered_at');
        expect($payload)->toHaveKey('triggered_by');
    });

    it('includes default payload values', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class, [
            'default_payload' => [
                'custom_key' => 'custom_value',
            ],
        ]);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('custom_key');
        expect($payload['custom_key'])->toBe('custom_value');
    });

    it('merges default payload with base payload', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class, [
            'default_payload' => [
                'user_id' => 123,
            ],
        ]);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('triggered_at');
        expect($payload)->toHaveKey('triggered_by');
        expect($payload)->toHaveKey('user_id');
    });
});

// =============================================================================
// Registration Tests
// =============================================================================

describe('registration', function () {
    it('does not throw on register', function () {
        $brick = $this->registry->resolve(OnManualTrigger::class);

        // Manual triggers don't register automatically
        $brick->register();

        expect(true)->toBeTrue();
    });
});
