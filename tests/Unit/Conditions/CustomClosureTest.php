<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\CustomClosure;
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
        $brick = $this->registry->resolve(CustomClosure::class);

        expect($brick->name())->toBe('Custom Closure');
        expect($brick->category())->toBe('Advanced');
        expect($brick->icon())->toBe('code');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(CustomClosure::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('closure');
    });

    it('has correct labels', function () {
        $brick = $this->registry->resolve(CustomClosure::class);

        expect($brick->onTrueLabel())->toBe('Condition Met');
        expect($brick->onFalseLabel())->toBe('Condition Not Met');
    });
});

// =============================================================================
// Security - Config Check Tests
// =============================================================================

describe('security config check', function () {
    it('returns false when custom code is disabled', function () {
        config(['autobuilder.security.allow_custom_code' => false]);

        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return true;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('logs error when custom code is disabled', function () {
        config(['autobuilder.security.allow_custom_code' => false]);

        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return true;',
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context);

        $errorLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $errorMessage = array_values($errorLogs)[0]['message'];
        expect($errorMessage)->toContain('disabled');
    });

    it('evaluates when custom code is enabled', function () {
        config(['autobuilder.security.allow_custom_code' => true]);

        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return true;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });
});

// =============================================================================
// Empty/Invalid Closure Tests
// =============================================================================

describe('empty and invalid closure', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('returns false for empty closure', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => '',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('returns false for null closure', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => null,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('logs warning for invalid PHP syntax', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'this is not valid php {{{',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();

        $errorLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();
    });
});

// =============================================================================
// Boolean Return Value Tests
// =============================================================================

describe('boolean return values', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('returns true for true', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return true;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns false for false', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return false;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('casts truthy value to true', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return "non-empty string";',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('casts truthy integer to true', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return 1;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('casts falsy value to false', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return 0;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('casts empty string to false', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return "";',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('casts null to false', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return null;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('casts non-empty array to true', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return [1, 2, 3];',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('casts empty array to false', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return [];',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });
});

// =============================================================================
// Context/Payload Access Tests
// =============================================================================

describe('context and payload access', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('can access payload data for comparison', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $payload["status"] === "active";',
        ]);

        $context = new FlowContext('flow-1', ['status' => 'active']);
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns false when payload condition not met', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $payload["status"] === "active";',
        ]);

        $context = new FlowContext('flow-1', ['status' => 'inactive']);
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('can perform complex payload comparisons', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $payload["amount"] > 100 && $payload["approved"] === true;',
        ]);

        $context = new FlowContext('flow-1', ['amount' => 150, 'approved' => true]);
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('can access nested payload data', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $payload["user"]["role"] === "admin";',
        ]);

        $context = new FlowContext('flow-1', ['user' => ['role' => 'admin']]);
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('can use context methods', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $context->has("required_field");',
        ]);

        $context = new FlowContext('flow-1', ['required_field' => 'value']);
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });
});

// =============================================================================
// Exception Handling Tests
// =============================================================================

describe('exception handling', function () {
    beforeEach(function () {
        config(['autobuilder.security.allow_custom_code' => true]);
    });

    it('returns false and logs error on exception', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'throw new Exception("Test exception");',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();

        $errorLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $errorMessage = array_values($errorLogs)[0]['message'];
        expect($errorMessage)->toContain('Test exception');
    });

    it('handles undefined variable gracefully', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $undefined_variable === true;',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('handles missing array key gracefully', function () {
        $brick = $this->registry->resolve(CustomClosure::class, [
            'closure' => 'return $payload["nonexistent"] === "value";',
        ]);

        $context = new FlowContext('flow-1', []);
        $result = $brick->evaluate($context);

        // Should handle the warning/error and return false
        expect($result)->toBeFalse();
    });
});
