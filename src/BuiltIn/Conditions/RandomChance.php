<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * Random Chance condition for A/B testing and probabilistic flows.
 *
 * Returns true based on a percentage chance (0-100).
 */
class RandomChance extends Condition
{
    public function name(): string
    {
        return 'Random Chance';
    }

    public function description(): string
    {
        return 'Returns true based on a percentage probability. Useful for A/B testing.';
    }

    public function icon(): string
    {
        return 'dice-5';
    }

    public function category(): string
    {
        return 'Logic';
    }

    public function fields(): array
    {
        return [
            Number::make('percentage')
                ->label('Chance (%)')
                ->description('Probability of returning true (0-100)')
                ->min(0)
                ->max(100)
                ->default(50)
                ->required(),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $percentage = (float) $this->config('percentage', 50);

        // Clamp between 0 and 100
        $percentage = max(0, min(100, $percentage));

        // Generate random number between 0 and 100
        $random = mt_rand(0, 10000) / 100;

        $result = $random < $percentage;

        $context->log('info', sprintf(
            'RandomChance: %s%% chance, rolled %.2f, result: %s',
            $percentage,
            $random,
            $result ? 'PASS' : 'FAIL'
        ));

        return $result;
    }
}
