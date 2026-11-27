<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Jobs;

use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Support\AutoBuilderLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Runs a flow in response to a dispatched trigger.
 *
 * Dispatched on the queue for async flows, and through the sync bus for
 * flows in sync mode.
 */
class RunFlowJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $flowId,
        public readonly string $trigger,
        public readonly array $payload
    ) {}

    public function handle(FlowRunner $runner): void
    {
        $flow = Flow::find($this->flowId);

        if (! $flow) {
            AutoBuilderLogger::warning("[AutoBuilder] Flow {$this->flowId} not found");

            return;
        }

        if (! $flow->active) {
            AutoBuilderLogger::info("[AutoBuilder] Flow {$this->flowId} is not active, skipping");

            return;
        }

        try {
            $result = $runner->run($flow, $this->payload);

            AutoBuilderLogger::info("[AutoBuilder] Flow {$this->flowId} completed with status: {$result->status}", [
                'run_id' => $result->context->runId,
            ]);
        } catch (\Throwable $e) {
            AutoBuilderLogger::error("[AutoBuilder] Flow {$this->flowId} execution failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        }
    }
}
