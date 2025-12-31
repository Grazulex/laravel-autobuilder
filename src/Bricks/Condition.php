<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Flow\FlowContext;

abstract class Condition extends Brick
{
    public function type(): string
    {
        return 'condition';
    }

    /**
     * Evaluate the condition
     *
     * @return bool True if condition passes, false otherwise
     */
    abstract public function evaluate(FlowContext $context): bool;

    /**
     * Get the label for the "true" branch
     */
    public function onTrueLabel(): string
    {
        return 'Yes';
    }

    /**
     * Get the label for the "false" branch
     */
    public function onFalseLabel(): string
    {
        return 'No';
    }
}
