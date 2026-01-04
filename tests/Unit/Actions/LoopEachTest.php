<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LoopEach;
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
        $brick = $this->registry->resolve(LoopEach::class);

        expect($brick->name())->toBe('For Each');
        expect($brick->category())->toBe('Flow Control');
        expect($brick->icon())->toBe('repeat');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('collection');
        expect($fieldNames)->toContain('flow_id');
        expect($fieldNames)->toContain('item_variable');
        expect($fieldNames)->toContain('index_variable');
        expect($fieldNames)->toContain('pass_context');
        expect($fieldNames)->toContain('collect_results');
        expect($fieldNames)->toContain('results_variable');
        expect($fieldNames)->toContain('stop_on_error');
        expect($fieldNames)->toContain('max_iterations');
        expect($fieldNames)->toContain('delay_between');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(LoopEach::class);

        expect($brick->description())->toContain('Iterate');
    });

    it('has 10 fields total', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(10);
    });
});

// =============================================================================
// Null Collection Handling Tests
// =============================================================================

describe('null collection handling', function () {
    it('logs warning when collection is null', function () {
        $brick = $this->registry->resolve(LoopEach::class, [
            'collection' => 'missing_items',
            'flow_id' => 'test-flow',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();

        $firstWarning = array_values($warningLogs)[0]['message'];
        expect($firstWarning)->toContain('null');
    });

    it('sets empty results when collection is null', function () {
        $brick = $this->registry->resolve(LoopEach::class, [
            'collection' => 'missing_items',
            'flow_id' => 'test-flow',
            'results_variable' => 'loop_results',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('loop_results'))->toBe([]);
        expect($result->get('foreach_count'))->toBe(0);
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default item_variable of item', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'item_variable');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('item');
    });

    it('uses default index_variable of index', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'index_variable');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('index');
    });

    it('uses default pass_context of true', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'pass_context');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });

    it('uses default collect_results of true', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'collect_results');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeTrue();
    });

    it('uses default results_variable of foreach_results', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'results_variable');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('foreach_results');
    });

    it('uses default stop_on_error of false', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'stop_on_error');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeFalse();
    });

    it('uses default max_iterations of 100', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'max_iterations');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(100);
    });

    it('uses default delay_between of 0', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'delay_between');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(0);
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('collection field is required', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'collection');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('flow_id field is required', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'flow_id');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('item_variable field is required', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'item_variable');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('collection field supports variables', function () {
        $brick = $this->registry->resolve(LoopEach::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'collection');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });
});
