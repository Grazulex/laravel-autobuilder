<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\AndCondition;
use Grazulex\AutoBuilder\BuiltIn\Conditions\OrCondition;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'status' => 'active',
        'role' => 'admin',
        'verified' => 'yes',
    ]);
});

describe('AndCondition', function () {
    it('has correct metadata', function () {
        $condition = new AndCondition();

        expect($condition->name())->toBe('AND Condition');
        expect($condition->category())->toBe('Logic');
        expect($condition->icon())->toBe('git-merge');
    });

    it('returns true when all conditions match', function () {
        $condition = new AndCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'active',
            'field2' => 'role',
            'value2' => 'admin',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when first condition fails', function () {
        $condition = new AndCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'inactive',
            'field2' => 'role',
            'value2' => 'admin',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });

    it('returns false when second condition fails', function () {
        $condition = new AndCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'active',
            'field2' => 'role',
            'value2' => 'user',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });

    it('supports optional third condition', function () {
        $condition = new AndCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'active',
            'field2' => 'role',
            'value2' => 'admin',
            'field3' => 'verified',
            'value3' => 'yes',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when third condition fails', function () {
        $condition = new AndCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'active',
            'field2' => 'role',
            'value2' => 'admin',
            'field3' => 'verified',
            'value3' => 'no',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });

    it('has correct labels', function () {
        $condition = new AndCondition();

        expect($condition->onTrueLabel())->toBe('All Match');
        expect($condition->onFalseLabel())->toBe('Not All Match');
    });
});

describe('OrCondition', function () {
    it('has correct metadata', function () {
        $condition = new OrCondition();

        expect($condition->name())->toBe('OR Condition');
        expect($condition->category())->toBe('Logic');
        expect($condition->icon())->toBe('git-branch');
    });

    it('returns true when first condition matches', function () {
        $condition = new OrCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'active',
            'field2' => 'role',
            'value2' => 'user',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns true when second condition matches', function () {
        $condition = new OrCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'inactive',
            'field2' => 'role',
            'value2' => 'admin',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('returns false when no conditions match', function () {
        $condition = new OrCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'inactive',
            'field2' => 'role',
            'value2' => 'user',
        ]);

        expect($condition->evaluate($this->context))->toBeFalse();
    });

    it('supports optional third condition', function () {
        $condition = new OrCondition();
        $condition->configure([
            'field1' => 'status',
            'value1' => 'inactive',
            'field2' => 'role',
            'value2' => 'user',
            'field3' => 'verified',
            'value3' => 'yes',
        ]);

        expect($condition->evaluate($this->context))->toBeTrue();
    });

    it('has correct labels', function () {
        $condition = new OrCondition();

        expect($condition->onTrueLabel())->toBe('Any Match');
        expect($condition->onFalseLabel())->toBe('None Match');
    });
});
