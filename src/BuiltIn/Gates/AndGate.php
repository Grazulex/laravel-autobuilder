<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Gates;

use Grazulex\AutoBuilder\Bricks\Gate;
use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * AND Gate - All conditions must be true.
 *
 * Waits for all incoming condition results and returns true
 * only if ALL inputs evaluate to true.
 */
class AndGate extends Gate
{
    public function name(): string
    {
        return 'AND Gate';
    }

    public function description(): string
    {
        return 'All conditions must pass. Outputs true only if every incoming condition is true.';
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
        return [];
    }

    public function evaluate(array $inputs, FlowContext $context): bool
    {
        if (empty($inputs)) {
            $context->log('warning', 'AndGate: No inputs received');

            return false;
        }

        $allTrue = true;
        $trueCount = 0;
        $falseCount = 0;

        foreach ($inputs as $sourceId => $result) {
            if ($result === true) {
                $trueCount++;
            } else {
                $falseCount++;
                $allTrue = false;
            }
        }

        $context->log('info', sprintf(
            'AndGate: %d/%d conditions passed (result: %s)',
            $trueCount,
            count($inputs),
            $allTrue ? 'PASS' : 'FAIL'
        ));

        return $allTrue;
    }
}
