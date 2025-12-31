<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class FieldEquals extends Condition
{
    public function name(): string
    {
        return 'Field Equals';
    }

    public function description(): string
    {
        return 'Checks if a field in the payload equals a specific value.';
    }

    public function icon(): string
    {
        return 'equal';
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
                ->placeholder('user.status')
                ->description('Use dot notation for nested fields')
                ->supportsVariables()
                ->required(),

            Select::make('operator')
                ->label('Operator')
                ->options([
                    '==' => 'Equals (==)',
                    '===' => 'Strict Equals (===)',
                    '!=' => 'Not Equals (!=)',
                    '!==' => 'Strict Not Equals (!==)',
                ])
                ->default('=='),

            Text::make('value')
                ->label('Value')
                ->supportsVariables()
                ->required(),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $fieldValue = $context->get($this->config('field'));
        $expectedValue = $this->resolveValue($this->config('value'), $context);
        $operator = $this->config('operator', '==');

        return match ($operator) {
            '==' => $fieldValue == $expectedValue,
            '===' => $fieldValue === $expectedValue,
            '!=' => $fieldValue != $expectedValue,
            '!==' => $fieldValue !== $expectedValue,
            default => false,
        };
    }
}
