<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\DispatchEvent;
use Grazulex\AutoBuilder\Flow\FlowContext;
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
        $brick = $this->registry->resolve(DispatchEvent::class);

        expect($brick->name())->toBe('Dispatch Event');
        expect($brick->category())->toBe('Events');
        expect($brick->icon())->toBe('zap');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(DispatchEvent::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('event_class');
        expect($fieldNames)->toContain('data');
        expect($fieldNames)->toContain('store_result');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(DispatchEvent::class);

        expect($brick->description())->toContain('event');
    });

    it('has 3 fields total', function () {
        $brick = $this->registry->resolve(DispatchEvent::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(3);
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('logs error when event class not found', function () {
        $brick = $this->registry->resolve(DispatchEvent::class, [
            'event_class' => 'NonExistent\\Event',
            'data' => '{}',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $firstError = array_values($errorLogs)[0]['message'];
        expect($firstError)->toContain('not found');
    });

    it('returns context when event class not found', function () {
        $brick = $this->registry->resolve(DispatchEvent::class, [
            'event_class' => 'NonExistent\\Event',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result)->toBeInstanceOf(FlowContext::class);
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('event_class field is required', function () {
        $brick = $this->registry->resolve(DispatchEvent::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'event_class');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('data field supports variables', function () {
        $brick = $this->registry->resolve(DispatchEvent::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'data');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });
});
