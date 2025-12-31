<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldEquals;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'status' => 'active',
        'count' => 10,
        'user' => [
            'role' => 'admin',
            'verified' => true,
        ],
    ]);
});

it('has correct metadata', function () {
    $condition = new FieldEquals();

    expect($condition->name())->toBe('Field Equals');
    expect($condition->type())->toBe('condition');
    expect($condition->category())->toBe('Comparison');
    expect($condition->icon())->toBe('equal');
});

it('returns true when field equals value (loose)', function () {
    $condition = new FieldEquals();
    $condition->configure([
        'field' => 'status',
        'value' => 'active',
        'operator' => '==',
    ]);

    expect($condition->evaluate($this->context))->toBeTrue();
});

it('returns false when field does not equal value', function () {
    $condition = new FieldEquals();
    $condition->configure([
        'field' => 'status',
        'value' => 'inactive',
        'operator' => '==',
    ]);

    expect($condition->evaluate($this->context))->toBeFalse();
});

it('supports nested field paths', function () {
    $condition = new FieldEquals();
    $condition->configure([
        'field' => 'user.role',
        'value' => 'admin',
        'operator' => '==',
    ]);

    expect($condition->evaluate($this->context))->toBeTrue();
});

it('supports strict comparison', function () {
    $condition = new FieldEquals();
    $condition->configure([
        'field' => 'count',
        'value' => '10', // String instead of int
        'operator' => '===',
    ]);

    expect($condition->evaluate($this->context))->toBeFalse();

    $condition->configure([
        'field' => 'count',
        'value' => 10,
        'operator' => '===',
    ]);

    expect($condition->evaluate($this->context))->toBeTrue();
});

it('has fields configuration', function () {
    $condition = new FieldEquals();
    $fields = $condition->fields();

    expect($fields)->toHaveCount(3);
    $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
    expect($fieldNames)->toContain('field');
    expect($fieldNames)->toContain('value');
    expect($fieldNames)->toContain('operator');
});
