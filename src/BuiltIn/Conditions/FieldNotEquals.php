<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class FieldNotEquals extends Condition
{
    public function name(): string
    {
        return 'Field Not Equals';
    }

    public function description(): string
    {
        return 'Checks if a field does NOT equal a specific value.';
    }

    public function icon(): string
    {
        return 'x-circle';
    }

    public function category(): string
    {
        return 'Comparison';
    }

    public function fields(): array
    {
        return [
            Text::make('field')
                ->label('Field')
                ->description('The field path to check (e.g., user.status)')
                ->supportsVariables()
                ->required(),

            Text::make('value')
                ->label('Value')
                ->description('The value to compare against')
                ->supportsVariables()
                ->required(),

            Select::make('operator')
                ->label('Comparison Type')
                ->options([
                    '!=' => 'Not Equal (loose)',
                    '!==' => 'Not Identical (strict)',
                ])
                ->default('!='),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $fieldPath = $this->config('field');
        $expectedValue = $this->resolveValue($this->config('value'), $context);
        $operator = $this->config('operator', '!=');

        $actualValue = $context->get($fieldPath);

        return match ($operator) {
            '!==' => $actualValue !== $expectedValue,
            default => $actualValue != $expectedValue,
        };
    }

    public function onTrueLabel(): string
    {
        return 'Not Equal';
    }

    public function onFalseLabel(): string
    {
        return 'Equal';
    }
}
