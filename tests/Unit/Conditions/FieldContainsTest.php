<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\FieldContains;
use Grazulex\AutoBuilder\Flow\FlowContext;

beforeEach(function () {
    $this->context = new FlowContext('flow-123', [
        'message' => 'Hello World',
        'email' => 'user@example.com',
        'tags' => ['php', 'laravel', 'testing'],
    ]);
});

it('has correct metadata', function () {
    $condition = new FieldContains();

    expect($condition->name())->toBe('Field Contains');
    expect($condition->type())->toBe('condition');
    expect($condition->category())->toBe('Comparison');
});

it('returns true when string contains value', function () {
    $condition = new FieldContains();
    $condition->configure([
        'field' => 'message',
        'needle' => 'World',
        'case_sensitive' => true,
    ]);

    expect($condition->evaluate($this->context))->toBeTrue();
});

it('returns false when string does not contain value', function () {
    $condition = new FieldContains();
    $condition->configure([
        'field' => 'message',
        'needle' => 'Goodbye',
        'case_sensitive' => true,
    ]);

    expect($condition->evaluate($this->context))->toBeFalse();
});

it('supports case insensitive search', function () {
    $condition = new FieldContains();
    $condition->configure([
        'field' => 'message',
        'needle' => 'world', // lowercase
        'case_sensitive' => false,
    ]);

    expect($condition->evaluate($this->context))->toBeTrue();
});

it('returns true when string contains value in email', function () {
    $condition = new FieldContains();
    $condition->configure([
        'field' => 'email',
        'needle' => 'example',
        'case_sensitive' => true,
    ]);

    expect($condition->evaluate($this->context))->toBeTrue();
});

it('returns false when string does not contain needle', function () {
    $condition = new FieldContains();
    $condition->configure([
        'field' => 'email',
        'needle' => 'gmail',
        'case_sensitive' => true,
    ]);

    expect($condition->evaluate($this->context))->toBeFalse();
});
