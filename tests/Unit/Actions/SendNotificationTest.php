<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SendNotification;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(SendNotification::class);

        expect($brick->name())->toBe('Send Notification');
        expect($brick->category())->toBe('Communication');
        expect($brick->icon())->toBe('bell');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(SendNotification::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('notification_class');
        expect($fieldNames)->toContain('notifiable_field');
        expect($fieldNames)->toContain('channels');
        expect($fieldNames)->toContain('data');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(SendNotification::class);

        expect($brick->description())->toContain('notification');
    });

    it('has 4 fields total', function () {
        $brick = $this->registry->resolve(SendNotification::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(4);
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('logs warning when notifiable not found', function () {
        $brick = $this->registry->resolve(SendNotification::class, [
            'notification_class' => 'App\\Notifications\\TestNotification',
            'notifiable_field' => 'missing_user',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();

        $firstWarning = array_values($warningLogs)[0]['message'];
        expect($firstWarning)->toContain('Notifiable not found');
    });

    it('logs error when notification class not found', function () {
        $brick = $this->registry->resolve(SendNotification::class, [
            'notification_class' => 'NonExistent\\Notification',
            'notifiable_field' => 'user',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', new stdClass());

        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $firstError = array_values($errorLogs)[0]['message'];
        expect($firstError)->toContain('not found');
    });

    it('returns context without action when notifiable missing', function () {
        $brick = $this->registry->resolve(SendNotification::class, [
            'notification_class' => 'App\\Notifications\\Test',
            'notifiable_field' => 'missing',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // Should return context but not crash
        expect($result)->toBeInstanceOf(FlowContext::class);
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default notifiable_field of user', function () {
        $brick = $this->registry->resolve(SendNotification::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'notifiable_field');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('user');
    });

    it('uses default channels of mail', function () {
        $brick = $this->registry->resolve(SendNotification::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'channels');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(['mail']);
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('channels field has correct options', function () {
        $brick = $this->registry->resolve(SendNotification::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'channels');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_keys($options))->toContain('mail');
        expect(array_keys($options))->toContain('database');
        expect(array_keys($options))->toContain('broadcast');
        expect(array_keys($options))->toContain('slack');
    });

    it('notification_class field is required', function () {
        $brick = $this->registry->resolve(SendNotification::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'notification_class');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });
});
