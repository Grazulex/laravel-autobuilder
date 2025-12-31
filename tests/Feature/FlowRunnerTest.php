<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LogMessage;
use Grazulex\AutoBuilder\BuiltIn\Actions\SetVariable;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldEquals;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\Events\FlowCompleted;
use Grazulex\AutoBuilder\Events\FlowStarted;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
    $this->runner = new FlowRunner($this->registry);
});

it('can run a simple flow with trigger and action', function () {
    Event::fake();

    $flow = Flow::create([
        'name' => 'Simple Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
            [
                'id' => 'action-1',
                'type' => 'action',
                'brick' => LogMessage::class,
                'config' => [
                    'message' => 'Hello World',
                    'level' => 'info',
                    'log_to_laravel' => false,
                    'log_to_context' => true,
                ],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'action-1'],
        ],
        'is_active' => true,
    ]);

    $result = $this->runner->run($flow, ['test' => 'value']);

    expect($result->status)->toBe('completed');
    expect($result->context->logs)->not->toBeEmpty();

    Event::assertDispatched(FlowStarted::class);
    Event::assertDispatched(FlowCompleted::class);
});

it('can run a flow with condition branching', function () {
    $flow = Flow::create([
        'name' => 'Conditional Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
            [
                'id' => 'condition-1',
                'type' => 'condition',
                'brick' => FieldEquals::class,
                'config' => [
                    'field' => 'status',
                    'value' => 'active',
                    'operator' => '==',
                ],
            ],
            [
                'id' => 'action-true',
                'type' => 'action',
                'brick' => SetVariable::class,
                'config' => [
                    'mode' => 'single',
                    'variable_name' => 'branch',
                    'variable_value' => 'true_branch',
                    'value_type' => 'string',
                ],
            ],
            [
                'id' => 'action-false',
                'type' => 'action',
                'brick' => SetVariable::class,
                'config' => [
                    'mode' => 'single',
                    'variable_name' => 'branch',
                    'variable_value' => 'false_branch',
                    'value_type' => 'string',
                ],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'condition-1'],
            ['source' => 'condition-1', 'target' => 'action-true', 'condition' => 'true'],
            ['source' => 'condition-1', 'target' => 'action-false', 'condition' => 'false'],
        ],
        'is_active' => true,
    ]);

    // Test true branch
    $result = $this->runner->run($flow, ['status' => 'active']);
    expect($result->status)->toBe('completed');
    expect($result->context->get('branch'))->toBe('true_branch');

    // Test false branch
    $result = $this->runner->run($flow, ['status' => 'inactive']);
    expect($result->status)->toBe('completed');
    expect($result->context->get('branch'))->toBe('false_branch');
});

it('saves flow run to database', function () {
    $flow = Flow::create([
        'name' => 'Database Test Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
        ],
        'edges' => [],
        'is_active' => true,
    ]);

    $result = $this->runner->run($flow, ['key' => 'value']);

    expect(FlowRun::count())->toBe(1);

    $run = FlowRun::first();
    expect($run->flow_id)->toBe($flow->id);
    expect($run->status)->toBe('completed');
    expect($run->payload)->toBe(['key' => 'value']);
});

it('can execute multiple actions in sequence', function () {
    $flow = Flow::create([
        'name' => 'Sequential Flow',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
            [
                'id' => 'action-1',
                'type' => 'action',
                'brick' => SetVariable::class,
                'config' => [
                    'mode' => 'single',
                    'variable_name' => 'step1',
                    'variable_value' => 'done',
                    'value_type' => 'string',
                ],
            ],
            [
                'id' => 'action-2',
                'type' => 'action',
                'brick' => SetVariable::class,
                'config' => [
                    'mode' => 'single',
                    'variable_name' => 'step2',
                    'variable_value' => 'done',
                    'value_type' => 'string',
                ],
            ],
            [
                'id' => 'action-3',
                'type' => 'action',
                'brick' => SetVariable::class,
                'config' => [
                    'mode' => 'single',
                    'variable_name' => 'step3',
                    'variable_value' => 'done',
                    'value_type' => 'string',
                ],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'action-1'],
            ['source' => 'action-1', 'target' => 'action-2'],
            ['source' => 'action-2', 'target' => 'action-3'],
        ],
        'is_active' => true,
    ]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('completed');
    expect($result->context->get('step1'))->toBe('done');
    expect($result->context->get('step2'))->toBe('done');
    expect($result->context->get('step3'))->toBe('done');
});

it('passes payload data through context', function () {
    $flow = Flow::create([
        'name' => 'Payload Test',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
        ],
        'edges' => [],
        'is_active' => true,
    ]);

    $payload = [
        'user' => [
            'name' => 'John',
            'email' => 'john@example.com',
        ],
        'order_id' => 'ORD-123',
    ];

    $result = $this->runner->run($flow, $payload);

    expect($result->context->get('user.name'))->toBe('John');
    expect($result->context->get('user.email'))->toBe('john@example.com');
    expect($result->context->get('order_id'))->toBe('ORD-123');
});
