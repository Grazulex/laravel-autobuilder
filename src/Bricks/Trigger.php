<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Events\TriggerDispatched;

abstract class Trigger extends Brick
{
    protected ?string $flowId = null;

    /**
     * Whether this trigger was unregistered. Laravel cannot detach a single
     * model or event callback, so the callbacks added by register() stay
     * attached; once unregistered, they no longer dispatch anything.
     */
    protected bool $unregistered = false;

    public function type(): string
    {
        return 'trigger';
    }

    /**
     * Register event listeners or webhook handlers
     */
    abstract public function register(): void;

    /**
     * Unregister event listeners.
     *
     * Switches the trigger off: callbacks added by register() stop dispatching.
     * Subclasses overriding this method to release extra resources should call
     * parent::unregister().
     */
    public function unregister(): void
    {
        $this->markAsUnregistered();
    }

    /**
     * Switch the trigger off without running unregister().
     */
    public function markAsUnregistered(): void
    {
        $this->unregistered = true;
    }

    /**
     * Whether the trigger was unregistered and no longer dispatches.
     */
    public function isUnregistered(): bool
    {
        return $this->unregistered;
    }

    /**
     * Dispatch the trigger with payload
     */
    protected function dispatch(array $payload): void
    {
        if ($this->flowId === null || $this->unregistered) {
            return;
        }

        event(new TriggerDispatched(
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
