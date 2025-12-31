<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class AndCondition extends Condition
{
    public function name(): string
    {
        return 'AND Condition';
    }

    public function description(): string
    {
        return 'Combines multiple field checks with AND logic (all must be true).';
    }

    public function icon(): string
    {
        return 'git-merge';
    }

    public function category(): string
    {
        return 'Logic';
    }

    public function fields(): array
    {
        return [
            Text::make('field1')
                ->label('First Field')
                ->supportsVariables()
                ->required(),

            Text::make('value1')
                ->label('First Value')
                ->supportsVariables()
                ->required(),

            Text::make('field2')
                ->label('Second Field')
                ->supportsVariables()
                ->required(),

            Text::make('value2')
                ->label('Second Value')
                ->supportsVariables()
                ->required(),

            Text::make('field3')
                ->label('Third Field (optional)')
                ->supportsVariables(),

            Text::make('value3')
                ->label('Third Value')
                ->supportsVariables(),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        // Check first condition
        $value1 = $context->get($this->config('field1'));
        $expected1 = $this->resolveValue($this->config('value1'), $context);
        if ($value1 != $expected1) {
            return false;
        }

        // Check second condition
        $value2 = $context->get($this->config('field2'));
        $expected2 = $this->resolveValue($this->config('value2'), $context);
        if ($value2 != $expected2) {
            return false;
        }

        // Check third condition if provided
        $field3 = $this->config('field3');
        if ($field3) {
            $value3 = $context->get($field3);
            $expected3 = $this->resolveValue($this->config('value3'), $context);
            if ($value3 != $expected3) {
                return false;
            }
        }

        return true;
    }

    public function onTrueLabel(): string
    {
        return 'All Match';
    }

    public function onFalseLabel(): string
    {
        return 'Not All Match';
    }
}
