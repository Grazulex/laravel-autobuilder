<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Gates\AndGate;
use Grazulex\AutoBuilder\BuiltIn\Gates\OrGate;
use Grazulex\AutoBuilder\Flow\FlowContext;

describe('AndGate', function () {
    it('returns true when all inputs are true', function () {
        $gate = new AndGate([]);
        $context = new FlowContext('flow-123');

        $inputs = [
            'condition-1' => true,
            'condition-2' => true,
            'condition-3' => true,
        ];

        expect($gate->evaluate($inputs, $context))->toBeTrue();
    });

    it('returns false when any input is false', function () {
        $gate = new AndGate([]);
        $context = new FlowContext('flow-123');

        $inputs = [
            'condition-1' => true,
            'condition-2' => false,
            'condition-3' => true,
        ];

        expect($gate->evaluate($inputs, $context))->toBeFalse();
    });

    it('returns false when all inputs are false', function () {
        $gate = new AndGate([]);
        $context = new FlowContext('flow-123');

        $inputs = [
            'condition-1' => false,
            'condition-2' => false,
        ];

        expect($gate->evaluate($inputs, $context))->toBeFalse();
    });

    it('returns false when no inputs provided', function () {
        $gate = new AndGate([]);
        $context = new FlowContext('flow-123');

        expect($gate->evaluate([], $context))->toBeFalse();
    });

    it('has correct metadata', function () {
        $gate = new AndGate([]);

        expect($gate->type())->toBe('gate');
        expect($gate->name())->toBe('AND Gate');
        expect($gate->minInputs())->toBe(2);
    });
});

describe('OrGate', function () {
    it('returns true when any input is true', function () {
        $gate = new OrGate([]);
        $context = new FlowContext('flow-123');

        $inputs = [
            'condition-1' => false,
            'condition-2' => true,
            'condition-3' => false,
        ];

        expect($gate->evaluate($inputs, $context))->toBeTrue();
    });

    it('returns true when all inputs are true', function () {
        $gate = new OrGate([]);
        $context = new FlowContext('flow-123');

        $inputs = [
            'condition-1' => true,
            'condition-2' => true,
        ];

        expect($gate->evaluate($inputs, $context))->toBeTrue();
    });

    it('returns false when all inputs are false', function () {
        $gate = new OrGate([]);
        $context = new FlowContext('flow-123');

        $inputs = [
            'condition-1' => false,
            'condition-2' => false,
            'condition-3' => false,
        ];

        expect($gate->evaluate($inputs, $context))->toBeFalse();
    });

    it('returns false when no inputs provided', function () {
        $gate = new OrGate([]);
        $context = new FlowContext('flow-123');

        expect($gate->evaluate([], $context))->toBeFalse();
    });

    it('has correct metadata', function () {
        $gate = new OrGate([]);

        expect($gate->type())->toBe('gate');
        expect($gate->name())->toBe('OR Gate');
        expect($gate->minInputs())->toBe(2);
    });
});
