<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LogMessage;
use Grazulex\AutoBuilder\BuiltIn\Actions\SetVariable;
use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldEquals;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
});

it('can register a trigger', function () {
    $this->registry->register(OnManualTrigger::class);

    expect($this->registry->has(OnManualTrigger::class))->toBeTrue();
});

it('can register a condition', function () {
    $this->registry->register(FieldEquals::class);

    expect($this->registry->has(FieldEquals::class))->toBeTrue();
});

it('can register an action', function () {
    $this->registry->register(LogMessage::class);

    expect($this->registry->has(LogMessage::class))->toBeTrue();
});

it('can discover built-in bricks', function () {
    $this->registry->discover();

    $all = $this->registry->all();

    expect($all['triggers'])->not->toBeEmpty();
    expect($all['conditions'])->not->toBeEmpty();
    expect($all['actions'])->not->toBeEmpty();
});

it('can resolve a brick with configuration', function () {
    $brick = $this->registry->resolve(SetVariable::class, [
        'mode' => 'single',
        'variable_name' => 'test',
        'variable_value' => 'value',
    ]);

    expect($brick)->toBeInstanceOf(Action::class);
    expect($brick->getConfig())->toBe([
        'mode' => 'single',
        'variable_name' => 'test',
        'variable_value' => 'value',
    ]);
});

it('throws exception for unknown brick class', function () {
    $this->registry->resolve('NonExistent\\Brick');
})->throws(\Grazulex\AutoBuilder\Exceptions\BrickException::class);

it('returns correct brick types', function () {
    $this->registry->discover();

    $triggers = $this->registry->getTriggers();
    $conditions = $this->registry->getConditions();
    $actions = $this->registry->getActions();

    foreach ($triggers as $trigger) {
        expect($trigger['type'])->toBe('trigger');
    }

    foreach ($conditions as $condition) {
        expect($condition['type'])->toBe('condition');
    }

    foreach ($actions as $action) {
        expect($action['type'])->toBe('action');
    }
});

it('brick toArray includes all metadata', function () {
    $brick = $this->registry->resolve(FieldEquals::class);
    $array = $brick->toArray();

    expect($array)->toHaveKeys([
        'class',
        'type',
        'name',
        'description',
        'icon',
        'category',
        'fields',
    ]);

    expect($array['class'])->toBe(FieldEquals::class);
    expect($array['type'])->toBe('condition');
    expect($array['fields'])->toBeArray();
});
