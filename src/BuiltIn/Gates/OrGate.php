<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Gates;

use Grazulex\AutoBuilder\Bricks\Gate;
use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * OR Gate - At least one condition must be true.
 *
 * Waits for all incoming condition results and returns true
 * if ANY input evaluates to true.
 */
class OrGate extends Gate
{
    public function name(): string
    {
        return 'OR Gate';
    }

    public function description(): string
    {
        return 'At least one condition must pass. Outputs true if any incoming condition is true.';
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
        return [];
    }

    public function evaluate(array $inputs, FlowContext $context): bool
    {
        if (empty($inputs)) {
            $context->log('warning', 'OrGate: No inputs received');

            return false;
        }

        $anyTrue = false;
        $trueCount = 0;

        foreach ($inputs as $sourceId => $result) {
            if ($result === true) {
                $trueCount++;
                $anyTrue = true;
            }
        }

        $context->log('info', sprintf(
            'OrGate: %d/%d conditions passed (result: %s)',
            $trueCount,
            count($inputs),
            $anyTrue ? 'PASS' : 'FAIL'
        ));

        return $anyTrue;
    }
}
