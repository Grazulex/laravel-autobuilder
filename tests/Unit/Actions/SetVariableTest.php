<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SetVariable;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'order_id' => 'ORD-001',
    ]);
});

it('has correct metadata', function () {
    $action = new SetVariable();

    expect($action->name())->toBe('Set Variable');
    expect($action->type())->toBe('action');
    expect($action->category())->toBe('Flow Control');
    expect($action->icon())->toBe('variable');
});

it('can set a single variable', function () {
    $action = new SetVariable();
    $action->configure([
        'mode' => 'single',
        'variable_name' => 'result',
        'variable_value' => 'success',
        'value_type' => 'string',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('result'))->toBe('success');
});

it('can set multiple variables', function () {
    $action = new SetVariable();
    $action->configure([
        'mode' => 'multiple',
        'variables' => [
            'foo' => 'bar',
            'count' => '42',
        ],
        'value_type' => 'auto',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('foo'))->toBe('bar');
    expect($result->get('count'))->toBe(42); // auto-detect as int
});

it('casts value to integer', function () {
    $action = new SetVariable();
    $action->configure([
        'mode' => 'single',
        'variable_name' => 'count',
        'variable_value' => '123',
        'value_type' => 'integer',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('count'))->toBe(123);
    expect($result->get('count'))->toBeInt();
});

it('casts value to float', function () {
    $action = new SetVariable();
    $action->configure([
        'mode' => 'single',
        'variable_name' => 'price',
        'variable_value' => '99.99',
        'value_type' => 'float',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('price'))->toBe(99.99);
    expect($result->get('price'))->toBeFloat();
});

it('casts value to boolean', function () {
    $action = new SetVariable();
    $action->configure([
        'mode' => 'single',
        'variable_name' => 'active',
        'variable_value' => 'true',
        'value_type' => 'boolean',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('active'))->toBeTrue();
});

it('parses JSON value', function () {
    $action = new SetVariable();
    $action->configure([
        'mode' => 'single',
        'variable_name' => 'data',
        'variable_value' => '{"name": "John", "age": 30}',
        'value_type' => 'json',
    ]);

    $result = $action->handle($this->context);

    expect($result->get('data'))->toBe(['name' => 'John', 'age' => 30]);
});

it('has fields configuration', function () {
    $action = new SetVariable();
    $fields = $action->fields();

    expect($fields)->not->toBeEmpty();

    $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);
    expect($fieldNames)->toContain('mode');
    expect($fieldNames)->toContain('variable_name');
    expect($fieldNames)->toContain('variable_value');
    expect($fieldNames)->toContain('value_type');
});
