<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\StopFlow;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'result' => 'success',
    ]);
});

it('has correct metadata', function () {
    $action = new StopFlow();

    expect($action->name())->toBe('Stop Flow');
    expect($action->type())->toBe('action');
    expect($action->category())->toBe('Flow Control');
    expect($action->icon())->toBe('stop-circle');
});

it('marks flow as stopped with complete status', function () {
    $action = new StopFlow();
    $action->configure([
        'stop_type' => 'complete',
        'reason' => 'All done',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('_stop_requested'))->toBeTrue();
    expect($result->get('_stop_type'))->toBe('complete');
    expect($result->get('_stop_reason'))->toBe('All done');
});

it('marks flow as stopped with fail status', function () {
    $action = new StopFlow();
    $action->configure([
        'stop_type' => 'fail',
        'reason' => 'Validation failed',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('_stop_type'))->toBe('fail');
});

it('marks flow as cancelled', function () {
    $action = new StopFlow();
    $action->configure([
        'stop_type' => 'cancel',
        'reason' => 'User cancelled',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('_stop_type'))->toBe('cancel');
});

it('stores final output from specified variable', function () {
    $action = new StopFlow();
    $action->configure([
        'stop_type' => 'complete',
        'output_variable' => 'result',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('_flow_output'))->toBe('success');
});

it('logs appropriate message based on stop type', function () {
    $action = new StopFlow();
    $action->configure([
        'stop_type' => 'fail',
        'reason' => 'Error occurred',
    ]);

    $result = $action->handle($this->context);

    expect($result->logs)->toHaveCount(1);
    expect($result->logs[0]['level'])->toBe('error');
});

it('has fields configuration', function () {
    $action = new StopFlow();
    $fields = $action->fields();

    expect($fields)->not->toBeEmpty();

    $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
    expect($fieldNames)->toContain('stop_type');
    expect($fieldNames)->toContain('reason');
    expect($fieldNames)->toContain('output_variable');
});
