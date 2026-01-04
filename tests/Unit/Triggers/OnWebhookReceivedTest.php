<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnWebhookReceived;
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
        $brick = $this->registry->resolve(OnWebhookReceived::class);

        expect($brick->name())->toBe('Webhook Received');
        expect($brick->type())->toBe('trigger');
        expect($brick->category())->toBe('External');
        expect($brick->icon())->toBe('webhook');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);

        expect($brick->description())->toContain('webhook');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('path');
        expect($fieldNames)->toContain('method');
        expect($fieldNames)->toContain('secret');
    });

    it('has 3 fields total', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(3);
    });
});

// =============================================================================
// Webhook URL Tests
// =============================================================================

describe('webhook url', function () {
    it('returns correct webhook url', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class, [
            'path' => 'my-webhook',
        ]);

        $url = $brick->getWebhookUrl();

        expect($url)->toContain('/autobuilder/webhook/my-webhook');
    });

    it('includes full url with domain', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class, [
            'path' => 'test-hook',
        ]);

        $url = $brick->getWebhookUrl();

        expect($url)->toStartWith('http');
        expect($url)->toContain('test-hook');
    });
});

// =============================================================================
// Sample Payload Tests
// =============================================================================

describe('sample payload', function () {
    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class, [
            'path' => 'test',
        ]);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('method');
        expect($payload)->toHaveKey('path');
        expect($payload)->toHaveKey('query');
        expect($payload)->toHaveKey('body');
        expect($payload)->toHaveKey('headers');
    });

    it('includes configured path in payload', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class, [
            'path' => 'my-custom-hook',
        ]);
        $payload = $brick->samplePayload();

        expect($payload['path'])->toBe('my-custom-hook');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('path field is required', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'path');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('path field has correct prefix', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'path');
        $prefix = array_values($field)[0]->toArray()['prefix'] ?? null;

        expect($prefix)->toBe('/autobuilder/webhook/');
    });

    it('method field has correct options', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'method');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_keys($options))->toContain('POST');
        expect(array_keys($options))->toContain('GET');
        expect(array_keys($options))->toContain('PUT');
        expect(array_keys($options))->toContain('PATCH');
        expect(array_keys($options))->toContain('DELETE');
        expect(array_keys($options))->toContain('ANY');
    });

    it('method field has default of POST', function () {
        $brick = $this->registry->resolve(OnWebhookReceived::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'method');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('POST');
    });
});
