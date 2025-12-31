<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Flow\FlowContext;

abstract class Trigger extends Brick
{
    protected ?string $flowId = null;

    public function type(): string
    {
        return 'trigger';
    }

    /**
     * Register event listeners or webhook handlers
     */
    abstract public function register(): void;

    /**
     * Unregister event listeners
     */
    public function unregister(): void
    {
        // Override in subclasses if needed
    }

    /**
     * Dispatch the trigger with payload
     */
    protected function dispatch(array $payload): void
    {
        if ($this->flowId === null) {
            return;
        }

        event(new \Grazulex\AutoBuilder\Events\TriggerDispatched(
            flowId: $this->flowId,
            trigger: static::class,
            payload: $payload
        ));
    }

    /**
     * Set the flow ID for this trigger
     */
    public function setFlowId(string $flowId): static
    {
        $this->flowId = $flowId;

        return $this;
    }

    /**
     * Get the flow ID
     */
    public function getFlowId(): ?string
    {
        return $this->flowId;
    }
}
