<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\RandomChance;
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
        $brick = $this->registry->resolve(RandomChance::class);

        expect($brick->name())->toBe('Random Chance');
        expect($brick->category())->toBe('Logic');
        expect($brick->icon())->toBe('dice-5');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(RandomChance::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('percentage');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(RandomChance::class);

        expect($brick->description())->toContain('percentage');
    });

    it('has 1 field total', function () {
        $brick = $this->registry->resolve(RandomChance::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(1);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns true when percentage is 100', function () {
        $brick = $this->registry->resolve(RandomChance::class, [
            'percentage' => 100,
        ]);

        $context = new FlowContext('flow-1');

        // With 100%, should always return true
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $brick->evaluate($context);
        }

        expect(array_filter($results))->toHaveCount(10);
    });

    it('returns false when percentage is 0', function () {
        $brick = $this->registry->resolve(RandomChance::class, [
            'percentage' => 0,
        ]);

        $context = new FlowContext('flow-1');

        // With 0%, should always return false
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $brick->evaluate($context);
        }

        expect(array_filter($results))->toHaveCount(0);
    });

    it('returns mixed results for 50 percent', function () {
        $brick = $this->registry->resolve(RandomChance::class, [
            'percentage' => 50,
        ]);

        $context = new FlowContext('flow-1');

        // With 50%, we expect some true and some false over many iterations
        $trueCount = 0;
        for ($i = 0; $i < 100; $i++) {
            if ($brick->evaluate($context)) {
                $trueCount++;
            }
        }

        // Should be roughly 50%, allow 20-80 range for randomness
        expect($trueCount)->toBeGreaterThan(20);
        expect($trueCount)->toBeLessThan(80);
    });

    it('logs the random result', function () {
        $brick = $this->registry->resolve(RandomChance::class, [
            'percentage' => 50,
        ]);

        $context = new FlowContext('flow-1');
        $brick->evaluate($context);

        $infoLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $firstLog = array_values($infoLogs)[0]['message'];
        expect($firstLog)->toContain('RandomChance');
    });

    it('clamps percentage above 100 to 100', function () {
        $brick = $this->registry->resolve(RandomChance::class, [
            'percentage' => 150,
        ]);

        $context = new FlowContext('flow-1');

        // Should behave like 100%
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $brick->evaluate($context);
        }

        expect(array_filter($results))->toHaveCount(10);
    });

    it('clamps percentage below 0 to 0', function () {
        $brick = $this->registry->resolve(RandomChance::class, [
            'percentage' => -50,
        ]);

        $context = new FlowContext('flow-1');

        // Should behave like 0%
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $brick->evaluate($context);
        }

        expect(array_filter($results))->toHaveCount(0);
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default percentage of 50', function () {
        $brick = $this->registry->resolve(RandomChance::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'percentage');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(50);
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('percentage field is required', function () {
        $brick = $this->registry->resolve(RandomChance::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'percentage');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('percentage field has min of 0', function () {
        $brick = $this->registry->resolve(RandomChance::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'percentage');
        $min = array_values($field)[0]->toArray()['min'] ?? null;

        expect((int) $min)->toBe(0);
    });

    it('percentage field has max of 100', function () {
        $brick = $this->registry->resolve(RandomChance::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'percentage');
        $max = array_values($field)[0]->toArray()['max'] ?? null;

        expect((int) $max)->toBe(100);
    });
});
