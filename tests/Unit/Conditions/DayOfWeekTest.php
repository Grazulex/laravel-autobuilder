<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\DayOfWeek;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);

        expect($brick->name())->toBe('Day of Week');
        expect($brick->category())->toBe('Time');
        expect($brick->icon())->toBe('calendar-days');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('days');
        expect($fieldNames)->toContain('timezone');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);

        expect($brick->description())->toContain('day');
    });

    it('has 2 fields total', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns true when current day is in selected days', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 10:00:00')); // Monday = 1

        $brick = $this->registry->resolve(DayOfWeek::class, [
            'days' => ['1'], // Monday
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('returns false when current day is not in selected days', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 10:00:00')); // Monday = 1

        $brick = $this->registry->resolve(DayOfWeek::class, [
            'days' => ['0', '6'], // Sunday, Saturday
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();

        Carbon::setTestNow();
    });

    it('returns true when multiple days are selected and today matches', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-17 10:00:00')); // Wednesday = 3

        $brick = $this->registry->resolve(DayOfWeek::class, [
            'days' => ['1', '3', '5'], // Mon, Wed, Fri
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('returns false when no days selected', function () {
        $brick = $this->registry->resolve(DayOfWeek::class, [
            'days' => [],
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('respects timezone setting', function () {
        // 23:00 UTC on Sunday = Monday in Asia/Tokyo (+9)
        Carbon::setTestNow(Carbon::parse('2024-01-14 23:00:00', 'UTC'));

        $brick = $this->registry->resolve(DayOfWeek::class, [
            'days' => ['1'], // Monday
            'timezone' => 'Asia/Tokyo',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });
});

// =============================================================================
// Labels Tests
// =============================================================================

describe('labels', function () {
    it('has correct true label', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);

        expect($brick->onTrueLabel())->toBe('Matching Day');
    });

    it('has correct false label', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);

        expect($brick->onFalseLabel())->toBe('Other Day');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('days field is required', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'days');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('days field has all weekday options', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'days');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect($options)->toHaveCount(7);
        // Keys can be strings or integers depending on PHP version
        $keys = array_map('strval', array_column($options, 'value'));
        expect($keys)->toContain('0'); // Sunday
        expect($keys)->toContain('1'); // Monday
        expect($keys)->toContain('6'); // Saturday
    });

    it('days field is multiple select', function () {
        $brick = $this->registry->resolve(DayOfWeek::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'days');
        $multiple = array_values($field)[0]->toArray()['multiple'] ?? false;

        expect($multiple)->toBeTrue();
    });
});
