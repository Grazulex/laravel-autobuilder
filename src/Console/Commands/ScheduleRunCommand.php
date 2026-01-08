<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Console\Commands;

use Cron\CronExpression;
use DateTimeZone;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduleRunCommand extends Command
{
    protected $signature = 'autobuilder:schedule-run {--dry-run : Show which flows would run without executing}';

    protected $description = 'Run scheduled flows that are due';

    public function handle(FlowRunner $runner, BrickRegistry $registry): int
    {
        $flows = $this->getScheduledFlows();

        if ($flows->isEmpty()) {
            $this->info('No scheduled flows found.');

            return self::SUCCESS;
        }

        $this->info("Found {$flows->count()} scheduled flow(s).");

        $executedCount = 0;

        foreach ($flows as $flow) {
            try {
                if ($this->shouldRunFlow($flow, $registry)) {
                    if ($this->option('dry-run')) {
                        $this->line("  [DRY-RUN] Would run: {$flow->name}");
                        $executedCount++;

                        continue;
                    }

                    $this->line("  Running: {$flow->name}");

                    $triggerConfig = $this->getTriggerConfig($flow);
                    $payload = [
                        'scheduled_at' => now()->toIso8601String(),
                        'timezone' => $triggerConfig['timezone'] ?? config('app.timezone'),
                        'frequency' => $triggerConfig['frequency'] ?? 'unknown',
                    ];

                    $result = $runner->run($flow, $payload);

                    if ($result->isCompleted()) {
                        $this->info("    Completed successfully.");
                        $executedCount++;
                    } elseif ($result->isFailed()) {
                        $this->error("    Failed: " . $result->error?->getMessage());
                        Log::error("[AutoBuilder] Scheduled flow {$flow->id} failed", [
                            'error' => $result->error?->getMessage(),
                        ]);
                    } elseif ($result->isPaused()) {
                        $this->warn("    Paused. Run ID: " . $result->context->runId);
                        $executedCount++;
                    }
                }
            } catch (Throwable $e) {
                $this->error("  Error processing flow {$flow->name}: {$e->getMessage()}");
                Log::error("[AutoBuilder] Error processing scheduled flow {$flow->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $action = $this->option('dry-run') ? 'would be executed' : 'executed';
        $this->newLine();
        $this->info("{$executedCount} flow(s) {$action}.");

        return self::SUCCESS;
    }

    /**
     * Get all active flows with OnSchedule trigger
     */
    protected function getScheduledFlows()
    {
        return Flow::active()
            ->where('trigger_type', OnSchedule::class)
            ->get();
    }

    /**
     * Determine if a flow should run now based on its cron expression
     */
    protected function shouldRunFlow(Flow $flow, BrickRegistry $registry): bool
    {
        $triggerConfig = $this->getTriggerConfig($flow);

        if (empty($triggerConfig)) {
            return false;
        }

        // Resolve the OnSchedule trigger to get the cron expression
        $trigger = $registry->resolve(OnSchedule::class, $triggerConfig);

        if (! $trigger instanceof OnSchedule) {
            return false;
        }

        $cronExpression = $trigger->getCronExpression();
        $timezone = new DateTimeZone($triggerConfig['timezone'] ?? config('app.timezone'));

        try {
            $cron = new CronExpression($cronExpression);

            return $cron->isDue('now', $timezone);
        } catch (Throwable $e) {
            Log::warning("[AutoBuilder] Invalid cron expression for flow {$flow->id}: {$cronExpression}");

            return false;
        }
    }

    /**
     * Get trigger configuration from flow
     */
    protected function getTriggerConfig(Flow $flow): array
    {
        // First check the dedicated trigger_config field
        if (! empty($flow->trigger_config)) {
            return $flow->trigger_config;
        }

        // Fallback: find trigger node in nodes array
        foreach ($flow->nodes ?? [] as $node) {
            if (($node['type'] ?? '') === 'trigger') {
                return $node['data']['config'] ?? $node['config'] ?? [];
            }
        }

        return [];
    }
}
