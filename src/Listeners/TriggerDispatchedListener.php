<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Listeners;

use Grazulex\AutoBuilder\Events\TriggerDispatched;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TriggerDispatchedListener implements ShouldQueue
{
    public function __construct(
        protected FlowRunner $runner
    ) {}

    /**
     * Handle the trigger dispatched event
     */
    public function handle(TriggerDispatched $event): void
    {
        Log::info("[AutoBuilder] Trigger dispatched for flow {$event->flowId}", [
            'trigger' => $event->trigger,
            'payload' => $event->payload,
        ]);

        // Load the flow
        $flow = Flow::find($event->flowId);

        if (! $flow) {
            Log::warning("[AutoBuilder] Flow {$event->flowId} not found");
            return;
        }

        if (! $flow->active) {
            Log::info("[AutoBuilder] Flow {$event->flowId} is not active, skipping");
            return;
        }

        // Run the flow
        try {
            $result = $this->runner->run($flow, $event->payload);

            Log::info("[AutoBuilder] Flow {$event->flowId} completed with status: {$result->status}", [
                'run_id' => $result->context->runId,
            ]);
        } catch (\Throwable $e) {
            Log::error("[AutoBuilder] Flow {$event->flowId} execution failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Determine the queue the listener should be assigned to
     */
    public function viaQueue(): string
    {
        return config('autobuilder.execution.queue', 'default');
    }

    /**
     * Determine if the listener should be queued
     */
    public function shouldQueue(TriggerDispatched $event): bool
    {
        // Check if flow has sync mode enabled
        $flow = Flow::find($event->flowId);

        if ($flow && $flow->sync) {
            return false; // Execute synchronously
        }

        // Fall back to global config
        return config('autobuilder.execution.async', true);
    }
}
