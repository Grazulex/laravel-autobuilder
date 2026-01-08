<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\Delay;
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
        $brick = $this->registry->resolve(Delay::class);

        expect($brick->name())->toBe('Delay');
        expect($brick->category())->toBe('Flow Control');
        expect($brick->icon())->toBe('clock');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('duration');
        expect($fieldNames)->toContain('unit');
        expect($fieldNames)->toContain('mode');
        expect($fieldNames)->toContain('max_sync_seconds');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(Delay::class);

        expect($brick->description())->toContain('Pause');
    });

    it('has 4 fields total', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(4);
    });
});

// =============================================================================
// Sync Mode Tests
// =============================================================================

describe('sync mode', function () {
    it('stores delay info in context', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 1,
            'unit' => 'seconds',
            'mode' => 'sync',
            'max_sync_seconds' => 1,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('delay_requested'))->toBe(1);
        expect($result->get('delay_actual'))->toBe(1);
        expect($result->get('delay_started_at'))->not->toBeNull();
        expect($result->get('delay_completed_at'))->not->toBeNull();
    });

    it('caps duration to max_sync_seconds', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 100,
            'unit' => 'seconds',
            'mode' => 'sync',
            'max_sync_seconds' => 1,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // Requested 100 but capped to 1
        expect($result->get('delay_requested'))->toBe(100);
        expect($result->get('delay_actual'))->toBe(1);

        // Verify warning logged
        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();
        expect(array_values($warningLogs)[0]['message'])->toContain('capped');
    });

    it('logs completion message', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 1,
            'unit' => 'seconds',
            'mode' => 'sync',
            'max_sync_seconds' => 1,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        $allMessages = implode(' ', $messages);

        expect($allMessages)->toContain('Completed');
    });
});

// =============================================================================
// Unit Conversion Tests
// =============================================================================

describe('unit conversion', function () {
    it('converts minutes to seconds', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 2,
            'unit' => 'minutes',
            'mode' => 'sync',
            'max_sync_seconds' => 1, // Cap to 1 second for test
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // 2 minutes = 120 seconds
        expect($result->get('delay_requested'))->toBe(120);
        expect($result->get('delay_actual'))->toBe(1); // Capped
    });

    it('converts hours to seconds', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 1,
            'unit' => 'hours',
            'mode' => 'sync',
            'max_sync_seconds' => 1, // Cap to 1 second for test
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // 1 hour = 3600 seconds
        expect($result->get('delay_requested'))->toBe(3600);
        expect($result->get('delay_actual'))->toBe(1); // Capped
    });

    it('defaults to seconds for unknown unit', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 5,
            'unit' => 'unknown_unit',
            'mode' => 'sync',
            'max_sync_seconds' => 1,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('delay_requested'))->toBe(5);
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs delay start info with duration and unit', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 5,
            'unit' => 'seconds',
            'mode' => 'sync',
            'max_sync_seconds' => 1,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $firstLog = array_values($infoLogs)[0]['message'];
        expect($firstLog)->toContain('Delay');
        expect($firstLog)->toContain('5');
        expect($firstLog)->toContain('seconds');
        expect($firstLog)->toContain('sync');
    });

    it('logs mode in start message', function () {
        $brick = $this->registry->resolve(Delay::class, [
            'duration' => 1,
            'unit' => 'seconds',
            'mode' => 'sync',
            'max_sync_seconds' => 1,
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $firstLog = array_values($infoLogs)[0]['message'];
        expect($firstLog)->toContain('sync');
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default duration of 5', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $durationField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'duration');
        $defaultValue = array_values($durationField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(5);
    });

    it('uses default unit of seconds', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $unitField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'unit');
        $defaultValue = array_values($unitField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('seconds');
    });

    it('uses default mode of sync', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $modeField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'mode');
        $defaultValue = array_values($modeField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('sync');
    });

    it('uses default max_sync_seconds of 30', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $maxSyncField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'max_sync_seconds');
        $defaultValue = array_values($maxSyncField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(30);
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('unit field has correct options', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $unitField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'unit');
        $options = array_values($unitField)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('seconds');
        expect(array_column($options, 'value'))->toContain('minutes');
        expect(array_column($options, 'value'))->toContain('hours');
    });

    it('mode field has sync and pause options', function () {
        $brick = $this->registry->resolve(Delay::class);
        $fields = $brick->fields();

        $modeField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'mode');
        $options = array_values($modeField)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('sync');
        expect(array_column($options, 'value'))->toContain('pause');
    });
});
