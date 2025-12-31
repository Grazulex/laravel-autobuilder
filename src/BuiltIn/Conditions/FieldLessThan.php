<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class FieldLessThan extends Condition
{
    public function name(): string
    {
        return 'Field Less Than';
    }

    public function description(): string
    {
        return 'Checks if a numeric field is less than a value.';
    }

    public function icon(): string
    {
        return 'chevron-left';
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
                ->placeholder('inventory.stock')
                ->supportsVariables()
                ->required(),

            Select::make('operator')
                ->label('Operator')
                ->options([
                    '<' => 'Less Than (<)',
                    '<=' => 'Less Than or Equal (<=)',
                ])
                ->default('<'),

            Text::make('value')
                ->label('Value')
                ->supportsVariables()
                ->required(),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $fieldValue = (float) $context->get($this->config('field'), 0);
        $compareValue = (float) $this->resolveValue($this->config('value'), $context);
        $operator = $this->config('operator', '<');

        return match ($operator) {
            '<' => $fieldValue < $compareValue,
            '<=' => $fieldValue <= $compareValue,
            default => false,
        };
    }
}
