<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LogMessage;
use Grazulex\AutoBuilder\BuiltIn\Actions\SetVariable;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldEquals;
use Grazulex\AutoBuilder\BuiltIn\Gates\AndGate;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\Flow\FlowValidationResult;
use Grazulex\AutoBuilder\Flow\FlowValidator;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
    $this->validator = new FlowValidator($this->registry);
});

// =============================================================================
// Empty Flow Validation
// =============================================================================

describe('empty flow validation', function () {
    it('returns error for empty flow with no nodes', function () {
        $flow = Flow::create([
            'name' => 'Empty Flow',
            'nodes' => [],
            'edges' => [],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();
        expect($result->hasErrors())->toBeTrue();
        expect($result->errorCount())->toBe(1);
        expect($result->errors[0]['type'])->toBe('empty_flow');
    });

    it('returns error for flow with null nodes', function () {
        $flow = new Flow();
        $flow->name = 'Null Nodes Flow';
        $flow->nodes = null;
        $flow->edges = [];
        $flow->save();

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();
        expect($result->errors[0]['type'])->toBe('empty_flow');
    });
});

// =============================================================================
// Trigger Validation
// =============================================================================

describe('trigger validation', function () {
    it('returns error for flow without trigger', function () {
        $flow = Flow::create([
            'name' => 'No Trigger Flow',
            'nodes' => [
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'brick' => SetVariable::class,
                        'config' => [
                            'mode' => 'single',
                            'variable_name' => 'test',
                            'variable_value' => 'value',
                        ],
                    ],
                ],
            ],
            'edges' => [],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();
        expect($result->hasErrors())->toBeTrue();

        $triggerErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'no_trigger');
        expect($triggerErrors)->not->toBeEmpty();
    });

    it('is valid when flow has a trigger', function () {
        $flow = Flow::create([
            'name' => 'With Trigger Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'brick' => SetVariable::class,
                        'config' => [
                            'mode' => 'single',
                            'variable_name' => 'test',
                            'variable_value' => 'value',
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $triggerErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'no_trigger');
        expect($triggerErrors)->toBeEmpty();
    });
});

// =============================================================================
// Required Fields Validation
// =============================================================================

describe('required fields validation', function () {
    it('returns error for node without brick class', function () {
        $flow = Flow::create([
            'name' => 'Missing Brick Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'label' => 'Broken Action',
                        // No brick class defined
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();

        $brickErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'missing_brick');
        expect($brickErrors)->not->toBeEmpty();
        expect(array_values($brickErrors)[0]['node_id'])->toBe('action-1');
    });

    it('returns error for required field that is empty', function () {
        $flow = Flow::create([
            'name' => 'Empty Required Field Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'label' => 'Log Message',
                        'brick' => LogMessage::class,
                        'config' => [
                            // 'message' is required but empty
                            'message' => '',
                            'level' => 'info',
                        ],
                        'fields' => [
                            [
                                'name' => 'message',
                                'label' => 'Message',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();

        $fieldErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'required_field');
        expect($fieldErrors)->not->toBeEmpty();
    });

    it('is valid when required field is filled', function () {
        $flow = Flow::create([
            'name' => 'Valid Required Field Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'label' => 'Log Message',
                        'brick' => LogMessage::class,
                        'config' => [
                            'message' => 'Hello World',
                            'level' => 'info',
                        ],
                        'fields' => [
                            [
                                'name' => 'message',
                                'label' => 'Message',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $fieldErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'required_field');
        expect($fieldErrors)->toBeEmpty();
    });

    it('accepts variable templating syntax in required fields', function () {
        $flow = Flow::create([
            'name' => 'Template Syntax Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'label' => 'Log Message',
                        'brick' => LogMessage::class,
                        'config' => [
                            'message' => '{{ trigger.value }}',
                            'level' => 'info',
                        ],
                        'fields' => [
                            [
                                'name' => 'message',
                                'label' => 'Message',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $fieldErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'required_field');
        expect($fieldErrors)->toBeEmpty();
    });

    it('skips hidden required fields', function () {
        $flow = Flow::create([
            'name' => 'Hidden Field Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'brick' => SetVariable::class,
                        'config' => [],
                        'fields' => [
                            [
                                'name' => 'secret_field',
                                'label' => 'Secret',
                                'required' => true,
                                'hidden' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $fieldErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'required_field' && $e['field'] === 'secret_field');
        expect($fieldErrors)->toBeEmpty();
    });
});

// =============================================================================
// Orphan Node Validation
// =============================================================================

describe('orphan node validation', function () {
    it('warns when trigger has no outgoing connections', function () {
        $flow = Flow::create([
            'name' => 'Orphan Trigger Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'label' => 'Orphan Trigger',
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->hasWarnings())->toBeTrue();

        $orphanWarnings = array_filter($result->warnings, fn ($w) => $w['type'] === 'orphan_trigger');
        expect($orphanWarnings)->not->toBeEmpty();
    });

    it('warns when action has no incoming connections', function () {
        $flow = Flow::create([
            'name' => 'Orphan Action Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'label' => 'Orphan Action',
                        'brick' => SetVariable::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [],
        ]);

        $result = $this->validator->validate($flow);

        $orphanWarnings = array_filter($result->warnings, fn ($w) => $w['type'] === 'orphan_action');
        expect($orphanWarnings)->not->toBeEmpty();
    });

    it('warns when condition has no incoming connections', function () {
        $flow = Flow::create([
            'name' => 'Orphan Condition Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'condition-1',
                    'type' => 'condition',
                    'data' => [
                        'label' => 'Orphan Condition',
                        'brick' => FieldEquals::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [],
        ]);

        $result = $this->validator->validate($flow);

        $conditionWarnings = array_filter($result->warnings, fn ($w) => $w['type'] === 'orphan_condition');
        expect($conditionWarnings)->not->toBeEmpty();
    });

    it('warns when condition has no outgoing connections', function () {
        $flow = Flow::create([
            'name' => 'Condition No Outgoing Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'condition-1',
                    'type' => 'condition',
                    'data' => [
                        'label' => 'Dead End Condition',
                        'brick' => FieldEquals::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'condition-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $conditionWarnings = array_filter(
            $result->warnings,
            fn ($w) => $w['type'] === 'orphan_condition' && str_contains($w['message'], 'no outgoing')
        );
        expect($conditionWarnings)->not->toBeEmpty();
    });

    it('warns when gate has less than 2 inputs', function () {
        $flow = Flow::create([
            'name' => 'Single Input Gate Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'gate-1',
                    'type' => 'gate',
                    'data' => [
                        'label' => 'Single Input Gate',
                        'brick' => AndGate::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'gate-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $gateWarnings = array_filter($result->warnings, fn ($w) => $w['type'] === 'gate_inputs');
        expect($gateWarnings)->not->toBeEmpty();
    });

    it('warns when gate has no outgoing connections', function () {
        $flow = Flow::create([
            'name' => 'Gate No Outgoing Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'condition-1',
                    'type' => 'condition',
                    'data' => [
                        'brick' => FieldEquals::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'condition-2',
                    'type' => 'condition',
                    'data' => [
                        'brick' => FieldEquals::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'gate-1',
                    'type' => 'gate',
                    'data' => [
                        'label' => 'Dead End Gate',
                        'brick' => AndGate::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'condition-1'],
                ['source' => 'trigger-1', 'target' => 'condition-2'],
                ['source' => 'condition-1', 'target' => 'gate-1'],
                ['source' => 'condition-2', 'target' => 'gate-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        $gateWarnings = array_filter($result->warnings, fn ($w) => $w['type'] === 'orphan_gate');
        expect($gateWarnings)->not->toBeEmpty();
    });
});

// =============================================================================
// Connection Validation
// =============================================================================

describe('connection validation', function () {
    it('returns error for edge with invalid source node', function () {
        $flow = Flow::create([
            'name' => 'Invalid Source Edge Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'nonexistent-node', 'target' => 'trigger-1'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();

        $edgeErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'invalid_edge' && str_contains($e['message'], 'source'));
        expect($edgeErrors)->not->toBeEmpty();
    });

    it('returns error for edge with invalid target node', function () {
        $flow = Flow::create([
            'name' => 'Invalid Target Edge Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'nonexistent-node'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeFalse();

        $edgeErrors = array_filter($result->errors, fn ($e) => $e['type'] === 'invalid_edge' && str_contains($e['message'], 'target'));
        expect($edgeErrors)->not->toBeEmpty();
    });

    it('warns for self-loop connection', function () {
        $flow = Flow::create([
            'name' => 'Self Loop Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'label' => 'Loop Action',
                        'brick' => SetVariable::class,
                        'config' => [],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
                ['source' => 'action-1', 'target' => 'action-1'], // Self-loop
            ],
        ]);

        $result = $this->validator->validate($flow);

        $loopWarnings = array_filter($result->warnings, fn ($w) => $w['type'] === 'self_loop');
        expect($loopWarnings)->not->toBeEmpty();
    });
});

// =============================================================================
// FlowValidationResult Tests
// =============================================================================

describe('FlowValidationResult', function () {
    it('isValid returns true when no errors', function () {
        $result = new FlowValidationResult(
            valid: true,
            errors: [],
            warnings: [],
            flowId: 'test-flow-id'
        );

        expect($result->isValid())->toBeTrue();
    });

    it('isValid returns false when has errors', function () {
        $result = new FlowValidationResult(
            valid: false,
            errors: [['type' => 'test', 'message' => 'Test error', 'node_id' => null]],
            warnings: [],
            flowId: 'test-flow-id'
        );

        expect($result->isValid())->toBeFalse();
    });

    it('hasWarnings returns true when warnings present', function () {
        $result = new FlowValidationResult(
            valid: true,
            errors: [],
            warnings: [['type' => 'test', 'message' => 'Test warning', 'node_id' => null]],
            flowId: 'test-flow-id'
        );

        expect($result->hasWarnings())->toBeTrue();
    });

    it('hasErrors returns true when errors present', function () {
        $result = new FlowValidationResult(
            valid: false,
            errors: [['type' => 'test', 'message' => 'Test error', 'node_id' => null]],
            warnings: [],
            flowId: 'test-flow-id'
        );

        expect($result->hasErrors())->toBeTrue();
    });

    it('errorCount returns correct count', function () {
        $result = new FlowValidationResult(
            valid: false,
            errors: [
                ['type' => 'a', 'message' => 'Error A', 'node_id' => null],
                ['type' => 'b', 'message' => 'Error B', 'node_id' => null],
                ['type' => 'c', 'message' => 'Error C', 'node_id' => null],
            ],
            warnings: [],
            flowId: 'test-flow-id'
        );

        expect($result->errorCount())->toBe(3);
    });

    it('warningCount returns correct count', function () {
        $result = new FlowValidationResult(
            valid: true,
            errors: [],
            warnings: [
                ['type' => 'a', 'message' => 'Warning A', 'node_id' => null],
                ['type' => 'b', 'message' => 'Warning B', 'node_id' => null],
            ],
            flowId: 'test-flow-id'
        );

        expect($result->warningCount())->toBe(2);
    });

    it('errorsForNode filters by node_id', function () {
        $result = new FlowValidationResult(
            valid: false,
            errors: [
                ['type' => 'a', 'message' => 'Error for node-1', 'node_id' => 'node-1'],
                ['type' => 'b', 'message' => 'Error for node-2', 'node_id' => 'node-2'],
                ['type' => 'c', 'message' => 'Another for node-1', 'node_id' => 'node-1'],
            ],
            warnings: [],
            flowId: 'test-flow-id'
        );

        $node1Errors = $result->errorsForNode('node-1');

        expect($node1Errors)->toHaveCount(2);
    });

    it('warningsForNode filters by node_id', function () {
        $result = new FlowValidationResult(
            valid: true,
            errors: [],
            warnings: [
                ['type' => 'a', 'message' => 'Warning for node-1', 'node_id' => 'node-1'],
                ['type' => 'b', 'message' => 'Warning for node-2', 'node_id' => 'node-2'],
            ],
            flowId: 'test-flow-id'
        );

        $node2Warnings = $result->warningsForNode('node-2');

        expect($node2Warnings)->toHaveCount(1);
    });

    it('toArray returns complete structure', function () {
        $result = new FlowValidationResult(
            valid: false,
            errors: [['type' => 'test', 'message' => 'Error', 'node_id' => 'node-1']],
            warnings: [['type' => 'warn', 'message' => 'Warning', 'node_id' => null]],
            flowId: 'flow-123'
        );

        $array = $result->toArray();

        expect($array['valid'])->toBeFalse();
        expect($array['flow_id'])->toBe('flow-123');
        expect($array['errors'])->toHaveCount(1);
        expect($array['warnings'])->toHaveCount(1);
        expect($array['summary']['error_count'])->toBe(1);
        expect($array['summary']['warning_count'])->toBe(1);
    });

    it('getSummary returns valid message when no issues', function () {
        $result = new FlowValidationResult(
            valid: true,
            errors: [],
            warnings: [],
            flowId: 'flow-123'
        );

        expect($result->getSummary())->toBe('Flow is valid and ready to activate.');
    });

    it('getSummary mentions warnings when valid with warnings', function () {
        $result = new FlowValidationResult(
            valid: true,
            errors: [],
            warnings: [['type' => 'warn', 'message' => 'Test', 'node_id' => null]],
            flowId: 'flow-123'
        );

        expect($result->getSummary())->toContain('warning');
    });

    it('getSummary mentions errors and warnings when invalid', function () {
        $result = new FlowValidationResult(
            valid: false,
            errors: [
                ['type' => 'a', 'message' => 'Error', 'node_id' => null],
                ['type' => 'b', 'message' => 'Error', 'node_id' => null],
            ],
            warnings: [['type' => 'warn', 'message' => 'Test', 'node_id' => null]],
            flowId: 'flow-123'
        );

        $summary = $result->getSummary();

        expect($summary)->toContain('2 error(s)');
        expect($summary)->toContain('1 warning(s)');
    });
});

// =============================================================================
// Valid Flow Test
// =============================================================================

describe('valid flow', function () {
    it('validates a complete well-formed flow', function () {
        $flow = Flow::create([
            'name' => 'Valid Complete Flow',
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnManualTrigger::class,
                        'config' => [],
                    ],
                ],
                [
                    'id' => 'condition-1',
                    'type' => 'condition',
                    'data' => [
                        'brick' => FieldEquals::class,
                        'config' => [
                            'field' => 'status',
                            'value' => 'active',
                        ],
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'data' => [
                        'brick' => SetVariable::class,
                        'config' => [
                            'mode' => 'single',
                            'variable_name' => 'result',
                            'variable_value' => 'done',
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'condition-1'],
                ['source' => 'condition-1', 'target' => 'action-1', 'sourceHandle' => 'true'],
            ],
        ]);

        $result = $this->validator->validate($flow);

        expect($result->isValid())->toBeTrue();
        expect($result->hasErrors())->toBeFalse();
    });
});
