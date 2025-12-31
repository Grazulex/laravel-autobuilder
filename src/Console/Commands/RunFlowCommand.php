<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Console\Commands;

use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Console\Command;

class RunFlowCommand extends Command
{
    protected $signature = 'autobuilder:run {flow : The flow ID or name} {--payload= : JSON payload}';

    protected $description = 'Run a flow manually';

    public function handle(FlowRunner $runner): int
    {
        $flowIdentifier = $this->argument('flow');

        $flow = Flow::where('id', $flowIdentifier)
            ->orWhere('name', $flowIdentifier)
            ->first();

        if (! $flow) {
            $this->error("Flow not found: {$flowIdentifier}");

            return self::FAILURE;
        }

        $payload = [];
        if ($payloadJson = $this->option('payload')) {
            $payload = json_decode($payloadJson, true) ?? [];
        }

        $this->info("Running flow: {$flow->name}");

        $result = $runner->run($flow, $payload);

        if ($result->isCompleted()) {
            $this->info('Flow completed successfully!');
        } elseif ($result->isFailed()) {
            $this->error('Flow failed: ' . $result->error?->getMessage());

            return self::FAILURE;
        } elseif ($result->isPaused()) {
            $this->warn('Flow paused. Run ID: ' . $result->context->runId);
        }

        // Show logs
        $this->newLine();
        $this->info('Execution logs:');
        foreach ($result->context->logs as $log) {
            $this->line("[{$log['level']}] {$log['message']}");
        }

        return self::SUCCESS;
    }
}
