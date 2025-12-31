<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Observers;

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Trigger\TriggerManager;

class FlowObserver
{
    public function __construct(
        protected TriggerManager $triggerManager
    ) {}

    /**
     * Handle the Flow "updated" event.
     */
    public function updated(Flow $flow): void
    {
        // Check if active status changed
        if ($flow->wasChanged('active')) {
            $this->triggerManager->refreshFlow($flow);
        }

        // Check if nodes changed (trigger might have been modified)
        if ($flow->wasChanged('nodes') && $flow->active) {
            $this->triggerManager->refreshFlow($flow);
        }
    }

    /**
     * Handle the Flow "deleted" event.
     */
    public function deleted(Flow $flow): void
    {
        $this->triggerManager->unregisterFlow($flow);
    }

    /**
     * Handle the Flow "restored" event.
     */
    public function restored(Flow $flow): void
    {
        if ($flow->active) {
            $this->triggerManager->registerFlow($flow);
        }
    }
}
