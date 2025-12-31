<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Trigger;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Log;

class TriggerManager
{
    /**
     * Registered triggers indexed by flow ID
     * @var array<string, Trigger>
     */
    protected array $registeredTriggers = [];

    public function __construct(
        protected BrickRegistry $registry
    ) {}

    /**
     * Register triggers for all active flows
     */
    public function bootActiveFlows(): void
    {
        $activeFlows = Flow::active()->get();

        foreach ($activeFlows as $flow) {
            $this->registerFlow($flow);
        }

        Log::debug("[AutoBuilder] Booted {$activeFlows->count()} active flows");
    }

    /**
     * Register trigger for a specific flow
     */
    public function registerFlow(Flow $flow): void
    {
        // Find trigger node in the flow
        $triggerNode = $this->findTriggerNode($flow);

        if (! $triggerNode) {
            Log::warning("[AutoBuilder] Flow {$flow->id} has no trigger node");
            return;
        }

        // Get brick class and config
        $brickClass = $triggerNode['data']['brick'] ?? $triggerNode['brick'] ?? null;
        $brickConfig = $triggerNode['data']['config'] ?? $triggerNode['config'] ?? [];

        if (! $brickClass) {
            Log::warning("[AutoBuilder] Flow {$flow->id} trigger node has no brick class");
            return;
        }

        try {
            // Resolve the trigger brick
            $trigger = $this->registry->resolve($brickClass, $brickConfig);

            if (! $trigger instanceof Trigger) {
                Log::warning("[AutoBuilder] Flow {$flow->id} brick {$brickClass} is not a Trigger");
                return;
            }

            // Set flow ID and register
            $trigger->setFlowId((string) $flow->id);
            $trigger->register();

            // Track registered trigger
            $this->registeredTriggers[$flow->id] = $trigger;

            Log::info("[AutoBuilder] Registered trigger for flow {$flow->id}: {$trigger->name()}");
        } catch (\Throwable $e) {
            Log::error("[AutoBuilder] Failed to register trigger for flow {$flow->id}: {$e->getMessage()}");
        }
    }

    /**
     * Unregister trigger for a specific flow
     */
    public function unregisterFlow(Flow $flow): void
    {
        if (! isset($this->registeredTriggers[$flow->id])) {
            return;
        }

        $trigger = $this->registeredTriggers[$flow->id];
        $trigger->unregister();

        unset($this->registeredTriggers[$flow->id]);

        Log::info("[AutoBuilder] Unregistered trigger for flow {$flow->id}");
    }

    /**
     * Refresh a flow's trigger (unregister then register)
     */
    public function refreshFlow(Flow $flow): void
    {
        $this->unregisterFlow($flow);

        if ($flow->active) {
            $this->registerFlow($flow);
        }
    }

    /**
     * Get all registered triggers
     * @return array<string, Trigger>
     */
    public function getRegisteredTriggers(): array
    {
        return $this->registeredTriggers;
    }

    /**
     * Find the trigger node in a flow
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
