<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Tests\Fixtures\Bricks;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Flow\FlowContext;
use RuntimeException;

/**
 * Test action that throws on its first `fail_times` executions.
 *
 * The number of executions is stored in the context under the `counter` key.
 */
class FlakyAction extends Action
{
    public function name(): string
    {
        return 'Flaky Action';
    }

    public function description(): string
    {
        return 'Fails a configurable number of times before succeeding.';
    }

    public function icon(): string
    {
        return 'zap';
    }

    public function fields(): array
    {
        return [];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $counter = (string) $this->config('counter', 'flaky_attempts');
        $attempts = (int) $context->get($counter, 0) + 1;

        $context->set($counter, $attempts);

        if ($attempts <= (int) $this->config('fail_times', 0)) {
            throw new RuntimeException("Flaky failure #{$attempts}");
        }

        return $context;
    }
}
