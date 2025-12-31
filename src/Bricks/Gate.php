<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * Abstract Gate class for combining multiple condition results.
 *
 * Gates wait for all incoming condition results before evaluating.
 * They output a single boolean result based on their logic (AND/OR).
 */
abstract class Gate extends Brick
{
    /**
     * Get the brick type
     */
    public function type(): string
    {
        return 'gate';
    }

    /**
     * Evaluate the gate logic based on incoming condition results.
     *
     * @param  array<string, bool>  $inputs  Map of source node ID => condition result
     */
    abstract public function evaluate(array $inputs, FlowContext $context): bool;

    /**
     * Get the minimum number of inputs required.
     */
    public function minInputs(): int
    {
        return 2;
    }

    /**
     * Get the maximum number of inputs allowed (null = unlimited).
     */
    public function maxInputs(): ?int
    {
        return null;
    }

    /**
     * Label shown on true output handle.
     */
    public function onTrueLabel(): string
    {
        return 'Pass';
    }

    /**
     * Label shown on false output handle.
     */
    public function onFalseLabel(): string
    {
        return 'Fail';
    }
}
