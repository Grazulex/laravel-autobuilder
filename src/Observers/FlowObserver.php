<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Observers;

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnWebhookReceived;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Trigger\TriggerManager;

class FlowObserver
{
    public function __construct(
        protected TriggerManager $triggerManager
    ) {}

    /**
     * Handle the Flow "creating" event.
     */
    public function creating(Flow $flow): void
    {
        $this->extractTriggerData($flow);
    }

    /**
     * Handle the Flow "updating" event.
     */
    public function updating(Flow $flow): void
    {
        // Only re-extract if nodes changed
        if ($flow->isDirty('nodes')) {
            $this->extractTriggerData($flow);
        }
    }

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

    /**
     * Extract trigger data from nodes and populate trigger_type, trigger_config, and webhook_path.
     */
    protected function extractTriggerData(Flow $flow): void
    {
        $triggerNode = $this->findTriggerNode($flow);

        if (! $triggerNode) {
            // No trigger node found, clear trigger data
            $flow->trigger_type = null;
            $flow->trigger_config = null;
            $flow->webhook_path = null;

            return;
        }

        // Extract brick class and config from node
        $brickClass = $triggerNode['data']['brick'] ?? $triggerNode['brick'] ?? null;
        $brickConfig = $triggerNode['data']['config'] ?? $triggerNode['config'] ?? [];

        // Set trigger_type and trigger_config
        $flow->trigger_type = $brickClass;
        $flow->trigger_config = $brickConfig;

        // Set webhook_path for OnWebhookReceived triggers
        if ($brickClass === OnWebhookReceived::class) {
            $flow->webhook_path = $brickConfig['path'] ?? null;
        } else {
            $flow->webhook_path = null;
        }
    }

    /**
     * Find the trigger node in a flow's nodes array.
     */
    protected function findTriggerNode(Flow $flow): ?array
    {
        foreach ($flow->nodes ?? [] as $node) {
            if (($node['type'] ?? '') === 'trigger') {
                return $node;
            }
        }

        return null;
    }
}
