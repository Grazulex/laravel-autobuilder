<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldGreaterThan;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldIsEmpty;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldLessThan;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldMatchesRegex;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldNotEquals;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'amount' => 100,
        'price' => 49.99,
        'empty_string' => '',
        'null_value' => null,
        'email' => 'test@example.com',
        'phone' => '+1-555-123-4567',
        'status' => 'active',
    ]);
});

describe('FieldGreaterThan', function () {
    it('returns true when field is greater than value', function () {
        $condition = new FieldGreaterThan();
        $condition->configure([
            'field' => 'amount',
            'value' => 50,
            'operator' => '>',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when field is not greater than value', function () {
        $condition = new FieldGreaterThan();
        $condition->configure([
            'field' => 'amount',
            'value' => 150,
            'operator' => '>',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });

    it('supports greater than or equal operator', function () {
        $condition = new FieldGreaterThan();
        $condition->configure([
            'field' => 'amount',
            'value' => 100,
            'operator' => '>=',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });
});

describe('FieldLessThan', function () {
    it('returns true when field is less than value', function () {
        $condition = new FieldLessThan();
        $condition->configure([
            'field' => 'price',
            'value' => 100,
            'operator' => '<',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('supports less than or equal operator', function () {
        $condition = new FieldLessThan();
        $condition->configure([
            'field' => 'price',
            'value' => 49.99,
            'operator' => '<=',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });
});

describe('FieldIsEmpty', function () {
    it('returns true when field is empty string', function () {
        $condition = new FieldIsEmpty();
        $condition->configure([
            'field' => 'empty_string',
            'invert' => false,
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns true when field is null', function () {
        $condition = new FieldIsEmpty();
        $condition->configure([
            'field' => 'null_value',
            'invert' => false,
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when field has value', function () {
        $condition = new FieldIsEmpty();
        $condition->configure([
            'field' => 'status',
            'invert' => false,
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });

    it('can invert the check (is not empty)', function () {
        $condition = new FieldIsEmpty();
        $condition->configure([
            'field' => 'status',
            'invert' => true,
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });
});

describe('FieldMatchesRegex', function () {
    it('returns true when field matches pattern', function () {
        $condition = new FieldMatchesRegex();
        $condition->configure([
            'field' => 'email',
            'pattern' => '/^[\w\.-]+@[\w\.-]+\.\w+$/',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when field does not match pattern', function () {
        $condition = new FieldMatchesRegex();
        $condition->configure([
            'field' => 'email',
            'pattern' => '/^[0-9]+$/',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });
});

describe('FieldNotEquals', function () {
    it('returns true when field does not equal value', function () {
        $condition = new FieldNotEquals();
        $condition->configure([
            'field' => 'status',
            'value' => 'inactive',
            'operator' => '!=',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when field equals value', function () {
        $condition = new FieldNotEquals();
        $condition->configure([
            'field' => 'status',
            'value' => 'active',
            'operator' => '!=',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });
});
