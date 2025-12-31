<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * SwitchCase Condition - Multi-value comparison.
 *
 * Checks if a value matches one of several cases and stores
 * the matched case name in context for downstream branching.
 *
 * Usage: Connect true output to different branches, then use
 * FieldEquals on {{ switch_matched_case }} to route accordingly.
 */
class SwitchCase extends Condition
{
    public function name(): string
    {
        return 'Switch Case';
    }

    public function description(): string
    {
        return 'Check value against multiple cases. Sets matched case in context for branching.';
    }

    public function icon(): string
    {
        return 'git-branch';
    }

    public function category(): string
    {
        return 'Logic';
    }

    public function fields(): array
    {
        return [
            Text::make('value')
                ->label('Value to Check')
                ->description('The value to compare against cases (supports variables)')
                ->placeholder('{{ user.role }}')
                ->supportsVariables()
                ->required(),

            KeyValue::make('cases')
                ->label('Cases')
                ->description('Case name => expected value (e.g., admin => admin, user => user)')
                ->keyLabel('Case Name')
                ->valueLabel('Match Value')
                ->supportsVariables()
                ->required(),

            Text::make('default_case')
                ->label('Default Case')
                ->description('Case name when no match found (empty = return false)')
                ->placeholder('default'),

            Select::make('comparison')
                ->label('Comparison Type')
                ->options([
                    'loose' => 'Loose (==)',
                    'strict' => 'Strict (===)',
                    'contains' => 'Contains',
                    'starts_with' => 'Starts With',
                    'ends_with' => 'Ends With',
                    'regex' => 'Regex Match',
                ])
                ->default('loose'),

            Text::make('store_as')
                ->label('Store Matched Case As')
                ->description('Variable name to store the matched case name')
                ->default('switch_matched_case'),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $value = $this->resolveValue($this->config('value'), $context);
        $cases = $this->config('cases', []);
        $defaultCase = $this->config('default_case', '');
        $comparison = $this->config('comparison', 'loose');
        $storeAs = $this->config('store_as', 'switch_matched_case');

        $matchedCase = null;

        // Check each case
        foreach ($cases as $caseName => $caseValue) {
            $resolvedCaseValue = $this->resolveValue($caseValue, $context);

            if ($this->compare($value, $resolvedCaseValue, $comparison)) {
                $matchedCase = $caseName;
                break;
            }
        }

        // Apply default if no match
        if ($matchedCase === null && $defaultCase) {
            $matchedCase = $defaultCase;
        }

        // Store result in context
        $context->set($storeAs, $matchedCase);
        $context->set('switch_value', $value);
        $context->set('switch_cases', array_keys($cases));

        if ($matchedCase !== null) {
            $context->log('info', "SwitchCase: Value '{$value}' matched case '{$matchedCase}'");

            return true;
        }

        $context->log('info', "SwitchCase: Value '{$value}' matched no case");

        return false;
    }

    private function compare(mixed $value, mixed $expected, string $mode): bool
    {
        return match ($mode) {
            'strict' => $value === $expected,
            'contains' => is_string($value) && is_string($expected) && str_contains($value, $expected),
            'starts_with' => is_string($value) && is_string($expected) && str_starts_with($value, $expected),
            'ends_with' => is_string($value) && is_string($expected) && str_ends_with($value, $expected),
            'regex' => is_string($value) && is_string($expected) && preg_match($expected, $value) === 1,
            default => $value == $expected, // loose comparison
        };
    }
}
