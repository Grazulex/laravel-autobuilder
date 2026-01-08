<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SubFlow;
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
        $brick = $this->registry->resolve(SubFlow::class);

        expect($brick->name())->toBe('Sub Flow');
        expect($brick->category())->toBe('Flow Control');
        expect($brick->icon())->toBe('git-branch');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('flow_id');
        expect($fieldNames)->toContain('payload_mode');
        expect($fieldNames)->toContain('custom_payload');
        expect($fieldNames)->toContain('merge_data');
        expect($fieldNames)->toContain('inherit_variables');
        expect($fieldNames)->toContain('import_variables');
        expect($fieldNames)->toContain('variable_prefix');
        expect($fieldNames)->toContain('store_result_as');
        expect($fieldNames)->toContain('stop_on_failure');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(SubFlow::class);

        expect($brick->description())->toContain('subroutine');
    });

    it('has 9 fields total', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(9);
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('logs error when flow not found', function () {
        $brick = $this->registry->resolve(SubFlow::class, [
            'flow_id' => 'non-existent-flow-id',
            'stop_on_failure' => false,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $firstError = array_values($errorLogs)[0]['message'];
        expect($firstError)->toContain('not found');
    });

    it('throws exception when flow not found and stop_on_failure is true', function () {
        $brick = $this->registry->resolve(SubFlow::class, [
            'flow_id' => 'non-existent-flow-id',
            'stop_on_failure' => true,
        ]);

        $context = new FlowContext('flow-1');

        expect(fn () => $brick->handle($context))->toThrow(RuntimeException::class);
    });

    it('returns context when flow not found and stop_on_failure is false', function () {
        $brick = $this->registry->resolve(SubFlow::class, [
            'flow_id' => 'non-existent-flow-id',
            'stop_on_failure' => false,
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
    it('uses default payload_mode of current', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'payload_mode');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('current');
    });

    it('uses default inherit_variables of true', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'inherit_variables');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });

    it('uses default import_variables of true', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'import_variables');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });

    it('uses default variable_prefix of empty string', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'variable_prefix');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('');
    });

    it('uses default store_result_as of subflow_result', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'store_result_as');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('subflow_result');
    });

    it('uses default stop_on_failure of true', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'stop_on_failure');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('flow_id field is required', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'flow_id');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('payload_mode field has correct options', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'payload_mode');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('current');
        expect(array_column($options, 'value'))->toContain('custom');
        expect(array_column($options, 'value'))->toContain('merge');
    });

    it('custom_payload field supports variables', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'custom_payload');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });

    it('merge_data field supports variables', function () {
        $brick = $this->registry->resolve(SubFlow::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'merge_data');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });
});
