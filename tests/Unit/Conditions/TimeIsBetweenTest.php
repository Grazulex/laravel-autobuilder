<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\TimeIsBetween;
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
        $brick = $this->registry->resolve(TimeIsBetween::class);

        expect($brick->name())->toBe('Time Is Between');
        expect($brick->category())->toBe('Time');
        expect($brick->icon())->toBe('clock');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(TimeIsBetween::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('start_time');
        expect($fieldNames)->toContain('end_time');
        expect($fieldNames)->toContain('timezone');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(TimeIsBetween::class);

        expect($brick->description())->toContain('time');
    });

    it('has 3 fields total', function () {
        $brick = $this->registry->resolve(TimeIsBetween::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(3);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns true when current time is within range', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 10:30:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('returns false when current time is before range', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 08:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();

        Carbon::setTestNow();
    });

    it('returns false when current time is after range', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 18:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();

        Carbon::setTestNow();
    });

    it('returns true when time equals start time', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('returns true when time equals end time', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('handles overnight ranges (start > end)', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 23:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '22:00',
            'end_time' => '06:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('handles overnight ranges early morning', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 04:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '22:00',
            'end_time' => '06:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();

        Carbon::setTestNow();
    });

    it('returns false for overnight ranges during day', function () {
        Carbon::setTestNow(Carbon::parse('2024-01-15 12:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '22:00',
            'end_time' => '06:00',
            'timezone' => 'UTC',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();

        Carbon::setTestNow();
    });

    it('respects timezone setting', function () {
        // 8:00 UTC = 17:00 in Asia/Tokyo (+9)
        Carbon::setTestNow(Carbon::parse('2024-01-15 08:00:00', 'UTC'));

        $brick = $this->registry->resolve(TimeIsBetween::class, [
            'start_time' => '09:00',
            'end_time' => '18:00',
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
        $brick = $this->registry->resolve(TimeIsBetween::class);

        expect($brick->onTrueLabel())->toBe('In Range');
    });

    it('has correct false label', function () {
        $brick = $this->registry->resolve(TimeIsBetween::class);

        expect($brick->onFalseLabel())->toBe('Outside Range');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('start_time field is required', function () {
        $brick = $this->registry->resolve(TimeIsBetween::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'start_time');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('end_time field is required', function () {
        $brick = $this->registry->resolve(TimeIsBetween::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'end_time');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });
});
