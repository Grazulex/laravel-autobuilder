<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LogMessage;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'order_id' => 'ORD-001',
        'customer' => 'John Doe',
    ]);
});

it('has correct metadata', function () {
    $action = new LogMessage();

    expect($action->name())->toBe('Log Message');
    expect($action->type())->toBe('action');
    expect($action->category())->toBe('Debugging');
    expect($action->icon())->toBe('file-text');
});

it('logs message to context', function () {
    $action = new LogMessage();
    $action->configure([
        'message' => 'Processing order',
        'level' => 'info',
        'log_to_laravel' => false,
        'log_to_context' => true,
        'include_context' => false,
    ]);

    $result = $action->handle($this->context);

    expect($result->logs)->toHaveCount(1);
    expect($result->logs[0]['level'])->toBe('info');
    expect($result->logs[0]['message'])->toBe('Processing order');
});

it('supports different log levels', function () {
    $action = new LogMessage();

    $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];

    foreach ($levels as $level) {
        $context = new FlowContext('flow-123');
        $action->configure([
            'message' => "Test {$level} message",
            'level' => $level,
            'log_to_laravel' => false,
            'log_to_context' => true,
            'include_context' => false,
        ]);

        $result = $action->handle($context);

        expect($result->logs[0]['level'])->toBe($level);
    }
});

it('has fields configuration', function () {
    $action = new LogMessage();
    $fields = $action->fields();

    expect($fields)->not->toBeEmpty();

    $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
    expect($fieldNames)->toContain('message');
    expect($fieldNames)->toContain('level');
    expect($fieldNames)->toContain('log_to_laravel');
    expect($fieldNames)->toContain('log_to_context');
});
