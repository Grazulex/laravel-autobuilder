<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Fields\Code;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Fields\Toggle;

it('can create a text field', function () {
    $field = Text::make('name')
        ->label('Name')
        ->description('Enter your name')
        ->placeholder('John Doe')
        ->required();

    $array = $field->toArray();

    expect($array['name'])->toBe('name');
    expect($array['type'])->toBe('text');
    expect($array['label'])->toBe('Name');
    expect($array['description'])->toBe('Enter your name');
    expect($array['placeholder'])->toBe('John Doe');
    expect($array['required'])->toBeTrue();
});

it('can create a textarea field', function () {
    $field = Textarea::make('content')
        ->label('Content')
        ->rows(6)
        ->maxLength(1000);

    $array = $field->toArray();

    expect($array['type'])->toBe('textarea');
    expect($array['rows'])->toBe(6);
    expect($array['maxLength'])->toBe(1000);
});

it('can create a select field', function () {
    $field = Select::make('status')
        ->label('Status')
        ->options(['active' => 'Active', 'inactive' => 'Inactive'])
        ->searchable()
        ->multiple();

    $array = $field->toArray();

    expect($array['type'])->toBe('select');
    expect($array['options'])->toBe([
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
    ]);
    expect($array['searchable'])->toBeTrue();
    expect($array['multiple'])->toBeTrue();
});

it('can create a toggle field', function () {
    $field = Toggle::make('enabled')
        ->label('Enabled')
        ->onLabel('Yes')
        ->offLabel('No')
        ->default(true);

    $array = $field->toArray();

    expect($array['type'])->toBe('toggle');
    expect($array['onLabel'])->toBe('Yes');
    expect($array['offLabel'])->toBe('No');
    expect($array['default'])->toBeTrue();
});

it('can create a number field', function () {
    $field = Number::make('amount')
        ->label('Amount')
        ->min(0)
        ->max(100)
        ->step(0.01);

    $array = $field->toArray();

    expect($array['type'])->toBe('number');
    expect($array['min'])->toBe(0.0);
    expect($array['max'])->toBe(100.0);
    expect($array['step'])->toBe(0.01);
});

it('validates number range', function () {
    $field = Number::make('amount')
        ->min(10)
        ->max(100);

    expect($field->validate(5))->toContain('The Amount field must be at least 10.');
    expect($field->validate(150))->toContain('The Amount field must not exceed 100.');
    expect($field->validate(50))->toBeEmpty();
});

it('can create a code field', function () {
    $field = Code::make('script')
        ->label('Script')
        ->language('javascript')
        ->height(300);

    $array = $field->toArray();

    expect($array['type'])->toBe('code');
    expect($array['language'])->toBe('javascript');
    expect($array['height'])->toBe(300);
});

it('can create a keyvalue field', function () {
    $field = KeyValue::make('headers')
        ->label('Headers')
        ->keyLabel('Header Name')
        ->valueLabel('Header Value')
        ->maxPairs(10);

    $array = $field->toArray();

    expect($array['type'])->toBe('keyvalue');
    expect($array['keyLabel'])->toBe('Header Name');
    expect($array['valueLabel'])->toBe('Header Value');
    expect($array['maxPairs'])->toBe(10);
});

it('supports conditional visibility', function () {
    $field = Text::make('api_key')
        ->visibleWhen('auth_type', 'api_key');

    $array = $field->toArray();

    expect($array['visibleWhen'])->toBe([
        'field' => 'auth_type',
        'value' => 'api_key',
    ]);
});

it('supports variable syntax', function () {
    $field = Text::make('message')
        ->supportsVariables();

    $array = $field->toArray();

    expect($array['supportsVariables'])->toBeTrue();
});
