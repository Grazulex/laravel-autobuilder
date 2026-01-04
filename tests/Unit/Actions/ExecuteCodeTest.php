<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\ExecuteCode;
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
        $brick = $this->registry->resolve(ExecuteCode::class);

        expect($brick->name())->toBe('Execute Code');
        expect($brick->category())->toBe('Advanced');
        expect($brick->icon())->toBe('terminal');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(ExecuteCode::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('code');
        expect($fieldNames)->toContain('store_result');
    });
});

// =============================================================================
// Security - Config Check Tests
// =============================================================================

describe('security config check', function () {
    it('blocks execution when custom code is disabled', function () {
        config(['autobuilder.security.allow_custom_code' => false]);

        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return "should not execute";',
        ]);

        $context = new FlowContext('flow-1', ['test' => 'value']);
        $result = $brick->handle($context);

        // Should log error
        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        // Should mention disabled in error message
        $errorMessage = array_values($errorLogs)[0]['message'];
        expect($errorMessage)->toContain('disabled');

        // Should NOT have executed the code
        expect($result->get('code_result'))->toBeNull();
    });

    it('allows execution when custom code is enabled', function () {
        config(['autobuilder.security.allow_custom_code' => true]);

        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return "executed";',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('code_result'))->toBe('executed');
    });
});

// =============================================================================
// Empty/Invalid Code Tests
// =============================================================================

describe('empty and invalid code', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('returns context unchanged for empty code', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => '',
        ]);

        $context = new FlowContext('flow-1', ['existing' => 'value']);
        $result = $brick->handle($context);

        expect($result->get('existing'))->toBe('value');
        expect($result->get('code_result'))->toBeNull();
    });

    it('returns context unchanged for null code', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => null,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('code_result'))->toBeNull();
    });

    it('logs error for invalid PHP syntax', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'this is not valid php {{{',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();
    });
});

// =============================================================================
// Return Value Handling Tests
// =============================================================================

describe('return value handling', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('stores scalar return value in configured variable', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return 42;',
            'store_result' => 'my_result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('my_result'))->toBe(42);
    });

    it('merges array return value into context', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return ["total" => 100, "tax" => 10];',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('total'))->toBe(100);
        expect($result->get('tax'))->toBe(10);
    });

    it('stores string return value', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return "hello world";',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('code_result'))->toBe('hello world');
    });

    it('stores boolean return value', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return true;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('code_result'))->toBeTrue();
    });
});

// =============================================================================
// Context/Payload Access Tests
// =============================================================================

describe('context and payload access', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('can access payload data', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return $payload["quantity"] * $payload["price"];',
        ]);

        $context = new FlowContext('flow-1', ['quantity' => 5, 'price' => 10]);
        $result = $brick->handle($context);

        expect($result->get('code_result'))->toBe(50);
    });

    it('can access context variables', function () {
        $context = new FlowContext('flow-1', ['initial' => 'data']);
        $context->set('computed', 'value');

        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return $payload["initial"] . " + " . $payload["computed"];',
        ]);

        $result = $brick->handle($context);

        expect($result->get('code_result'))->toBe('data + value');
    });

    it('can modify context directly', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => '$context->set("direct_set", "works"); return "done";',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('direct_set'))->toBe('works');
    });
});

// =============================================================================
// Exception Handling Tests
// =============================================================================

describe('exception handling', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('catches and logs runtime exceptions', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'throw new Exception("Test exception");',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $errorMessage = array_values($errorLogs)[0]['message'];
        expect($errorMessage)->toContain('Test exception');
    });

    it('catches division by zero', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return 1 / 0;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // Should not throw, should handle gracefully
        expect($result)->toBeInstanceOf(FlowContext::class);
    });

    it('catches undefined variable access', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return $undefined_variable;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();
    });
});

// =============================================================================
// Success Logging Tests
// =============================================================================

describe('success logging', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('logs success message on successful execution', function () {
        $brick = $this->registry->resolve(ExecuteCode::class, [
            'code' => 'return "success";',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        expect(implode(' ', $messages))->toContain('successfully');
    });
});
