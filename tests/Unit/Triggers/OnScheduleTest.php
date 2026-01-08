<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
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
        $brick = $this->registry->resolve(OnSchedule::class);

        expect($brick->name())->toBe('Scheduled');
        expect($brick->type())->toBe('trigger');
        expect($brick->category())->toBe('Time');
        expect($brick->icon())->toBe('clock');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(OnSchedule::class);

        expect($brick->description())->toContain('cron');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(OnSchedule::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('frequency');
        expect($fieldNames)->toContain('time');
        expect($fieldNames)->toContain('cron');
        expect($fieldNames)->toContain('timezone');
    });

    it('has 4 fields total', function () {
        $brick = $this->registry->resolve(OnSchedule::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(4);
    });
});

// =============================================================================
// Cron Expression Tests
// =============================================================================

describe('cron expression', function () {
    it('returns correct cron for everyMinute', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'everyMinute',
        ]);

        expect($brick->getCronExpression())->toBe('* * * * *');
    });

    it('returns correct cron for everyFiveMinutes', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'everyFiveMinutes',
        ]);

        expect($brick->getCronExpression())->toBe('*/5 * * * *');
    });

    it('returns correct cron for everyTenMinutes', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'everyTenMinutes',
        ]);

        expect($brick->getCronExpression())->toBe('*/10 * * * *');
    });

    it('returns correct cron for everyFifteenMinutes', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'everyFifteenMinutes',
        ]);

        expect($brick->getCronExpression())->toBe('*/15 * * * *');
    });

    it('returns correct cron for everyThirtyMinutes', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'everyThirtyMinutes',
        ]);

        expect($brick->getCronExpression())->toBe('*/30 * * * *');
    });

    it('returns correct cron for hourly', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'hourly',
        ]);

        expect($brick->getCronExpression())->toBe('0 * * * *');
    });

    it('returns correct cron for daily', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'daily',
        ]);

        expect($brick->getCronExpression())->toBe('0 0 * * *');
    });

    it('returns correct cron for weekly', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'weekly',
        ]);

        expect($brick->getCronExpression())->toBe('0 0 * * 0');
    });

    it('returns correct cron for monthly', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'monthly',
        ]);

        expect($brick->getCronExpression())->toBe('0 0 1 * *');
    });

    it('returns correct cron for dailyAt', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'dailyAt',
            'time' => '09:30',
        ]);

        expect($brick->getCronExpression())->toBe('30 09 * * *');
    });

    it('returns custom cron expression', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'custom',
            'cron' => '15 3 * * 1-5',
        ]);

        expect($brick->getCronExpression())->toBe('15 3 * * 1-5');
    });

    it('returns default cron for unknown frequency', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'unknown',
        ]);

        expect($brick->getCronExpression())->toBe('* * * * *');
    });
});

// =============================================================================
// Sample Payload Tests
// =============================================================================

describe('sample payload', function () {
    it('returns sample payload with required keys', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'daily',
        ]);
        $payload = $brick->samplePayload();

        expect($payload)->toHaveKey('scheduled_at');
        expect($payload)->toHaveKey('timezone');
        expect($payload)->toHaveKey('frequency');
    });

    it('includes configured frequency', function () {
        $brick = $this->registry->resolve(OnSchedule::class, [
            'frequency' => 'hourly',
        ]);
        $payload = $brick->samplePayload();

        expect($payload['frequency'])->toBe('hourly');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('frequency field is required', function () {
        $brick = $this->registry->resolve(OnSchedule::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'frequency');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('frequency field has correct options', function () {
        $brick = $this->registry->resolve(OnSchedule::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'frequency');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('everyMinute');
        expect(array_column($options, 'value'))->toContain('hourly');
        expect(array_column($options, 'value'))->toContain('daily');
        expect(array_column($options, 'value'))->toContain('weekly');
        expect(array_column($options, 'value'))->toContain('monthly');
        expect(array_column($options, 'value'))->toContain('custom');
    });

    it('time field is visible when frequency is dailyAt', function () {
        $brick = $this->registry->resolve(OnSchedule::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'time');
        $visibleWhen = array_values($field)[0]->toArray()['visibleWhen'] ?? null;

        expect($visibleWhen)->not->toBeNull();
    });

    it('cron field is visible when frequency is custom', function () {
        $brick = $this->registry->resolve(OnSchedule::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'cron');
        $visibleWhen = array_values($field)[0]->toArray()['visibleWhen'] ?? null;

        expect($visibleWhen)->not->toBeNull();
    });
});
