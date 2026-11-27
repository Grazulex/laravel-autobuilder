<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Listeners;

use Grazulex\AutoBuilder\Events\TriggerDispatched;
use Grazulex\AutoBuilder\Jobs\RunFlowJob;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Support\AutoBuilderLogger;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Not a queued listener on purpose: Laravel skips a queued listener whose
 * shouldQueue() returns false instead of running it inline, so the
 * sync/async decision is taken here and the flow runs in a job either way.
 */
class TriggerDispatchedListener
{
    /**
     * Handle the trigger dispatched event
     */
    public function handle(TriggerDispatched $event): void
    {
        AutoBuilderLogger::info("[AutoBuilder] Trigger dispatched for flow {$event->flowId}", [
            'trigger' => $event->trigger,
            'payload' => $event->payload,
        ]);

        $job = new RunFlowJob($event->flowId, $event->trigger, $event->payload);

        if ($this->shouldQueue($event)) {
            dispatch($job->onQueue($this->viaQueue()));

            return;
        }

        // dispatchNow() and not dispatch_sync(): the latter routes a ShouldQueue
        // job through the "sync" queue connection instead of calling it directly.
        app(Dispatcher::class)->dispatchNow($job);
    }

    /**
     * Determine the queue the flow run should be assigned to
     */
    public function viaQueue(): string
    {
        return config('autobuilder.execution.queue', 'default');
    }

    /**
     * Determine if the flow run should be queued
     */
    public function shouldQueue(TriggerDispatched $event): bool
    {
        // Check if flow has sync mode enabled
        $flow = Flow::find($event->flowId);

        if ($flow && $flow->sync) {
            return false; // Execute synchronously
        }

        // Fall back to global config
        return (bool) config('autobuilder.execution.async', true);
    }
}
