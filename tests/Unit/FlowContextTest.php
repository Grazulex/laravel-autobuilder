<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Flow\FlowContext;

it('can create a flow context', function () {
    $context = new FlowContext('flow-123', ['key' => 'value']);

    expect($context->flowId)->toBe('flow-123');
    expect($context->payload)->toBe(['key' => 'value']);
    expect($context->runId)->not->toBeEmpty();
});

it('can get values from payload', function () {
    $context = new FlowContext('flow-123', [
        'user' => [
            'name' => 'John',
            'email' => 'john@example.com',
        ],
    ]);

    expect($context->get('user.name'))->toBe('John');
    expect($context->get('user.email'))->toBe('john@example.com');
    expect($context->get('user.missing', 'default'))->toBe('default');
});

it('can set and get variables', function () {
    $context = new FlowContext('flow-123');

    $context->set('result', 'success');
    $context->set('data.nested', 'value');

    expect($context->get('result'))->toBe('success');
    expect($context->get('data.nested'))->toBe('value');
});

it('prioritizes variables over payload', function () {
    $context = new FlowContext('flow-123', ['key' => 'from_payload']);

    $context->set('key', 'from_variable');

    expect($context->get('key'))->toBe('from_variable');
});

it('can log messages', function () {
    $context = new FlowContext('flow-123');

    $context->info('Started');
    $context->warning('Something happened');
    $context->error('Failed');

    expect($context->logs)->toHaveCount(3);
    expect($context->logs[0]['level'])->toBe('info');
    expect($context->logs[1]['level'])->toBe('warning');
    expect($context->logs[2]['level'])->toBe('error');
});

it('can be paused', function () {
    $context = new FlowContext('flow-123');

    expect($context->isPaused())->toBeFalse();

    $context->setPaused(true, 'node-456');

    expect($context->isPaused())->toBeTrue();
    expect($context->pausedAt)->toBe('node-456');
});

it('can be serialized and restored', function () {
    $context = new FlowContext('flow-123', ['key' => 'value']);
    $context->set('var', 'test');
    $context->info('Log message');

    $array = $context->toArray();
    $restored = FlowContext::fromArray($array);

    expect($restored->flowId)->toBe($context->flowId);
    expect($restored->runId)->toBe($context->runId);
    expect($restored->get('key'))->toBe('value');
    expect($restored->get('var'))->toBe('test');
    expect($restored->logs)->toHaveCount(1);
});

it('can record gate inputs', function () {
    $context = new FlowContext('flow-123');

    $context->recordGateInput('gate-1', 'condition-1', true);
    $context->recordGateInput('gate-1', 'condition-2', false);

    $inputs = $context->getGateInputs('gate-1');

    expect($inputs)->toHaveCount(2);
    expect($inputs['condition-1'])->toBeTrue();
    expect($inputs['condition-2'])->toBeFalse();
});

it('can check if gate has all inputs', function () {
    $context = new FlowContext('flow-123');

    $context->recordGateInput('gate-1', 'condition-1', true);

    expect($context->hasAllGateInputs('gate-1', 2))->toBeFalse();

    $context->recordGateInput('gate-1', 'condition-2', true);

    expect($context->hasAllGateInputs('gate-1', 2))->toBeTrue();
});

it('can clear gate inputs', function () {
    $context = new FlowContext('flow-123');

    $context->recordGateInput('gate-1', 'condition-1', true);
    $context->recordGateInput('gate-1', 'condition-2', false);

    $context->clearGateInputs('gate-1');

    expect($context->getGateInputs('gate-1'))->toBeEmpty();
});

it('preserves gate inputs during serialization', function () {
    $context = new FlowContext('flow-123');
    $context->recordGateInput('gate-1', 'condition-1', true);
    $context->recordGateInput('gate-1', 'condition-2', false);

    $array = $context->toArray();
    $restored = FlowContext::fromArray($array);

    $inputs = $restored->getGateInputs('gate-1');
    expect($inputs)->toHaveCount(2);
    expect($inputs['condition-1'])->toBeTrue();
    expect($inputs['condition-2'])->toBeFalse();
});
