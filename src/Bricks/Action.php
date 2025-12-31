<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Flow\FlowContext;

abstract class Action extends Brick
{
    public function type(): string
    {
        return 'action';
    }

    /**
     * Execute the action
     *
     * @return FlowContext The updated context
     */
    abstract public function handle(FlowContext $context): FlowContext;

    /**
     * Rollback the action (if supported)
     */
    public function rollback(FlowContext $context): void
    {
        // Override in subclasses to implement rollback logic
    }

    /**
     * Check if this action can be retried on failure
     */
    public function canRetry(): bool
    {
        return true;
    }

    /**
     * Get the maximum number of retry attempts
     */
    public function maxRetries(): int
    {
        return 3;
    }

    /**
     * Get the delay between retries in seconds
     */
    public function retryDelay(): int
    {
        return 60;
    }
}
