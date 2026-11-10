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
     *
     * Return false for actions that must never run twice (non-idempotent side effects).
     */
    public function canRetry(): bool
    {
        return true;
    }

    /**
     * Get the maximum number of retry attempts after the first failure
     *
     * Defaults to the `autobuilder.execution.retry_attempts` configuration value.
     * A `retry_attempts` key in the node configuration takes precedence at run time.
     */
    public function maxRetries(): int
    {
        return max(0, (int) config('autobuilder.execution.retry_attempts', 3));
    }

    /**
     * Get the delay between retries in seconds
     *
     * Defaults to the `autobuilder.execution.retry_delay` configuration value.
     * A `retry_delay` key in the node configuration takes precedence at run time.
     */
    public function retryDelay(): int
    {
        return max(0, (int) config('autobuilder.execution.retry_delay', 60));
    }
}
