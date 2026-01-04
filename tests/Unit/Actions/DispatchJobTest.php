<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\DispatchJob;
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
        $brick = $this->registry->resolve(DispatchJob::class);

        expect($brick->name())->toBe('Dispatch Job');
        expect($brick->category())->toBe('Queue');
        expect($brick->icon())->toBe('layers');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(DispatchJob::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('job_class');
        expect($fieldNames)->toContain('data');
        expect($fieldNames)->toContain('queue');
        expect($fieldNames)->toContain('connection');
        expect($fieldNames)->toContain('delay');
        expect($fieldNames)->toContain('dispatch_sync');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(DispatchJob::class);

        expect($brick->description())->toContain('queue');
    });

    it('has 6 fields total', function () {
        $brick = $this->registry->resolve(DispatchJob::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(6);
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('logs error when job class not found', function () {
        $brick = $this->registry->resolve(DispatchJob::class, [
            'job_class' => 'NonExistent\\Job',
            'data' => '{}',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $firstError = array_values($errorLogs)[0]['message'];
        expect($firstError)->toContain('not found');
    });

    it('returns context when job class not found', function () {
        $brick = $this->registry->resolve(DispatchJob::class, [
            'job_class' => 'NonExistent\\Job',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result)->toBeInstanceOf(FlowContext::class);
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default dispatch_sync of false', function () {
        $brick = $this->registry->resolve(DispatchJob::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'dispatch_sync');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeFalse();
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('job_class field is required', function () {
        $brick = $this->registry->resolve(DispatchJob::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'job_class');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('data field supports variables', function () {
        $brick = $this->registry->resolve(DispatchJob::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'data');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });
});
