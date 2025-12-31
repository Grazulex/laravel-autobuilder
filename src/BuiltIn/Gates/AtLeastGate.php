<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Gates;

use Grazulex\AutoBuilder\Bricks\Gate;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * AtLeast Gate - At least N conditions must be true.
 *
 * Flexible gate that can act as OR (min=1), AND (min=all), or majority vote.
 */
class AtLeastGate extends Gate
{
    public function name(): string
    {
        return 'At Least';
    }

    public function description(): string
    {
        return 'Pass if at least N conditions are true. Flexible: min=1 acts as OR, min=all acts as AND.';
    }

    public function icon(): string
    {
        return 'check-check';
    }

    public function category(): string
    {
        return 'Logic';
    }

    public function fields(): array
    {
        return [
            Select::make('mode')
                ->label('Mode')
                ->options([
                    'count' => 'Fixed count (at least N)',
                    'percentage' => 'Percentage (at least X%)',
                    'majority' => 'Majority (> 50%)',
                    'all' => 'All (same as AND)',
                    'any' => 'Any (same as OR)',
                ])
                ->default('count'),

            Number::make('minimum')
                ->label('Minimum Required')
                ->description('Number of conditions that must pass')
                ->default(2)
                ->min(1)
                ->visibleWhen('mode', 'count'),

            Number::make('percentage')
                ->label('Minimum Percentage')
                ->description('Percentage of conditions that must pass (0-100)')
                ->default(50)
                ->min(0)
                ->max(100)
                ->visibleWhen('mode', 'percentage'),
        ];
    }

    public function evaluate(array $inputs, FlowContext $context): bool
    {
        if (empty($inputs)) {
            $context->log('warning', 'AtLeastGate: No inputs received');

            return false;
        }

        $total = count($inputs);
        $trueCount = count(array_filter($inputs, fn ($v) => $v === true));
        $mode = $this->config('mode', 'count');

        $required = match ($mode) {
            'count' => (int) $this->config('minimum', 2),
            'percentage' => (int) ceil($total * ((int) $this->config('percentage', 50)) / 100),
            'majority' => (int) ceil($total / 2) + ($total % 2 === 0 ? 1 : 0),
            'all' => $total,
            'any' => 1,
            default => 1,
        };

        // Ensure required doesn't exceed total
        $required = min($required, $total);

        $result = $trueCount >= $required;

        $context->log('info', sprintf(
            'AtLeastGate [%s]: %d/%d passed (required: %d) → %s',
            $mode,
            $trueCount,
            $total,
            $required,
            $result ? 'PASS' : 'FAIL'
        ));

        return $result;
    }

    public function onTrueLabel(): string
    {
        return 'Pass';
    }

    public function onFalseLabel(): string
    {
        return 'Fail';
    }
}
