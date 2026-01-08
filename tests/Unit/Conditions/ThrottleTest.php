<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\Throttle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
    Cache::flush();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(Throttle::class);

        expect($brick->name())->toBe('Throttle');
        expect($brick->category())->toBe('Flow Control');
        expect($brick->icon())->toBe('gauge');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('key');
        expect($fieldNames)->toContain('max_attempts');
        expect($fieldNames)->toContain('decay_seconds');
        expect($fieldNames)->toContain('on_throttle');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(Throttle::class);

        expect($brick->description())->toContain('limit');
    });

    it('has 4 fields total', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(4);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns true on first attempt', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle',
            'max_attempts' => 5,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns true while under limit', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_under',
            'max_attempts' => 3,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');

        expect($brick->evaluate($context))->toBeTrue(); // 1/3
        expect($brick->evaluate($context))->toBeTrue(); // 2/3
        expect($brick->evaluate($context))->toBeTrue(); // 3/3
    });

    it('returns false when limit exceeded', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_exceed',
            'max_attempts' => 2,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');

        $brick->evaluate($context); // 1/2
        $brick->evaluate($context); // 2/2
        $result = $brick->evaluate($context); // Should fail

        expect($result)->toBeFalse();
    });

    it('sets throttle context variables when within limit', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_vars',
            'max_attempts' => 5,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context);

        expect($context->get('throttle_exceeded'))->toBeFalse();
        expect($context->get('throttle_attempts'))->toBe(1);
        expect($context->get('throttle_max'))->toBe(5);
        expect($context->get('throttle_remaining'))->toBe(4);
    });

    it('sets throttle context variables when exceeded', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_vars_exceed',
            'max_attempts' => 1,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context); // Use the one attempt

        $context2 = new FlowContext('flow-2');
        $brick->evaluate($context2); // Should be throttled

        expect($context2->get('throttle_exceeded'))->toBeTrue();
        expect($context2->get('throttle_attempts'))->toBe(1);
        expect($context2->get('throttle_max'))->toBe(1);
    });

    it('logs warning when throttled', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_log',
            'max_attempts' => 1,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context);

        $context2 = new FlowContext('flow-2');
        $brick->evaluate($context2);

        $warningLogs = array_filter($context2->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();

        $firstWarning = array_values($warningLogs)[0]['message'];
        expect($firstWarning)->toContain('Rate limited');
    });

    it('logs info when within limit', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_info_log',
            'max_attempts' => 5,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context);

        $infoLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $firstInfo = array_values($infoLogs)[0]['message'];
        expect($firstInfo)->toContain('1/5');
    });

    it('returns true when on_throttle is skip', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'test_throttle_skip',
            'max_attempts' => 1,
            'decay_seconds' => 60,
            'on_throttle' => 'skip',
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context);

        $context2 = new FlowContext('flow-2');
        $result = $brick->evaluate($context2);

        expect($result)->toBeTrue(); // Returns true even when throttled
    });

    it('supports variable interpolation in key', function () {
        $brick = $this->registry->resolve(Throttle::class, [
            'key' => 'user_{{ user_id }}_action',
            'max_attempts' => 2,
            'decay_seconds' => 60,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user_id', 123);

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default max_attempts of 5', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'max_attempts');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(5);
    });

    it('uses default decay_seconds of 60', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'decay_seconds');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(60);
    });

    it('uses default on_throttle of false', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'on_throttle');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('false');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('key field is required', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'key');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('key field supports variables', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'key');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });

    it('max_attempts field is required', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'max_attempts');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('decay_seconds field is required', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'decay_seconds');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('on_throttle field has correct options', function () {
        $brick = $this->registry->resolve(Throttle::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'on_throttle');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('false');
        expect(array_column($options, 'value'))->toContain('skip');
    });
});
