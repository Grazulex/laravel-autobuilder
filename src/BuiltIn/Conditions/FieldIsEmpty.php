<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;

class FieldIsEmpty extends Condition
{
    public function name(): string
    {
        return 'Field Is Empty';
    }

    public function description(): string
    {
        return 'Checks if a field is empty, null, or does not exist.';
    }

    public function icon(): string
    {
        return 'square';
    }

    public function category(): string
    {
        return 'Comparison';
    }

    public function fields(): array
    {
        return [
            Text::make('field')
                ->label('Field Path')
                ->placeholder('user.phone')
                ->supportsVariables()
                ->required(),

            Toggle::make('invert')
                ->label('Invert (Is Not Empty)')
                ->description('Check if field is NOT empty instead')
                ->default(false),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $value = $context->get($this->config('field'));
        $invert = $this->config('invert', false);

        $isEmpty = $value === null
            || $value === ''
            || $value === []
            || (is_string($value) && trim($value) === '');

        return $invert ? ! $isEmpty : $isEmpty;
    }

    public function onTrueLabel(): string
    {
        return $this->config('invert', false) ? 'Not Empty' : 'Empty';
    }

    public function onFalseLabel(): string
    {
        return $this->config('invert', false) ? 'Empty' : 'Not Empty';
    }
}
