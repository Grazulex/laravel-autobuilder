<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Trigger;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Support\AutoBuilderLogger;

class TriggerManager
{
    /**
     * Registered triggers indexed by flow ID
     *
     * @var array<string, Trigger>
     */
    protected array $registeredTriggers = [];

    protected ActiveFlowsCache $activeFlowsCache;

    public function __construct(
        protected BrickRegistry $registry,
        ?ActiveFlowsCache $activeFlowsCache = null
    ) {
        $this->activeFlowsCache = $activeFlowsCache ?? app(ActiveFlowsCache::class);
    }

    /**
     * Register triggers for all active flows.
     *
     * Never throws: if the flows cannot be loaded (database down, tables not
     * migrated yet...), a warning is logged and no trigger is registered, so
     * the current request keeps working.
     */
    public function bootActiveFlows(): void
    {
        $this->bootFlows(quietWhenNotInstalled: false);
    }

    /**
     * Register triggers for all active flows, staying silent when the flows
     * table does not exist yet (fresh install, before `php artisan migrate`).
     *
     * Used in console processes, so a fresh install does not log a warning on
     * every artisan command. Any other failure is reported like bootActiveFlows().
     */
    public function bootActiveFlowsIfInstalled(): void
    {
        $this->bootFlows(quietWhenNotInstalled: true);
    }

    protected function bootFlows(bool $quietWhenNotInstalled): void
    {
        try {
            $activeFlows = $this->activeFlowsCache->get();
        } catch (\Throwable $e) {
            if ($quietWhenNotInstalled && $this->flowsTableIsMissing()) {
                AutoBuilderLogger::debug('[AutoBuilder] Flows table not migrated yet, triggers not registered');

                return;
            }

            AutoBuilderLogger::warning("[AutoBuilder] Could not load active flows, triggers not registered for this request: {$e->getMessage()}");

            return;
        }

        foreach ($activeFlows as $flow) {
            $this->registerFlow($flow);
        }

        AutoBuilderLogger::debug("[AutoBuilder] Booted {$activeFlows->count()} active flows");
    }

    /**
     * Register trigger for a specific flow
     */
    public function registerFlow(Flow $flow): void
    {
        // Find trigger node in the flow
        $triggerNode = $this->findTriggerNode($flow);

        if (! $triggerNode) {
            AutoBuilderLogger::warning("[AutoBuilder] Flow {$flow->id} has no trigger node");

            return;
        }

        // Get brick class and config
        $brickClass = $triggerNode['data']['brick'] ?? $triggerNode['brick'] ?? null;
        $brickConfig = $triggerNode['data']['config'] ?? $triggerNode['config'] ?? [];

        if (! $brickClass) {
            AutoBuilderLogger::warning("[AutoBuilder] Flow {$flow->id} trigger node has no brick class");

            return;
        }

        try {
            // Resolve the trigger brick
            $trigger = $this->registry->resolve($brickClass, $brickConfig);

            if (! $trigger instanceof Trigger) {
                AutoBuilderLogger::warning("[AutoBuilder] Flow {$flow->id} brick {$brickClass} is not a Trigger");

                return;
            }

            // Switch off a trigger already registered for this flow, so
            // registering twice does not run the flow twice per event
            $this->unregisterFlow($flow);

            // Set flow ID and register
            $trigger->setFlowId((string) $flow->id);
            $trigger->register();

            // Track registered trigger
            $this->registeredTriggers[$flow->id] = $trigger;

            AutoBuilderLogger::info("[AutoBuilder] Registered trigger for flow {$flow->id}: {$trigger->name()}");
        } catch (\Throwable $e) {
            AutoBuilderLogger::error("[AutoBuilder] Failed to register trigger for flow {$flow->id}: {$e->getMessage()}");
        }
    }

    /**
     * Unregister trigger for a specific flow.
     *
     * The trigger's callbacks stay attached to their model or event until the
     * process ends, but they no longer dispatch the flow.
     */
    public function unregisterFlow(Flow $flow): void
    {
        if (! isset($this->registeredTriggers[$flow->id])) {
            return;
        }

        $trigger = $this->registeredTriggers[$flow->id];
        unset($this->registeredTriggers[$flow->id]);

        try {
            $trigger->unregister();
        } catch (\Throwable $e) {
            AutoBuilderLogger::error("[AutoBuilder] Failed to unregister trigger for flow {$flow->id}: {$e->getMessage()}");
        } finally {
            // Also covers subclasses overriding unregister() without calling the parent
            $trigger->markAsUnregistered();
        }

        AutoBuilderLogger::info("[AutoBuilder] Unregistered trigger for flow {$flow->id}");
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
     *
     * @return array<string, Trigger>
     */
    public function getRegisteredTriggers(): array
    {
        return $this->registeredTriggers;
    }

    /**
     * Whether the flows table is confirmed missing. Only called after a
     * failure, so the normal boot path does not pay for the extra query.
     * Returns false when the check itself fails (database unreachable...).
     */
    protected function flowsTableIsMissing(): bool
    {
        try {
            $flow = new Flow;

            return ! $flow->getConnection()->getSchemaBuilder()->hasTable($flow->getTable());
        } catch (\Throwable) {
            return false;
        }
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
