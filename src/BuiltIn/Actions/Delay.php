<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Flow\FlowContext;

/**
 * Delay Action - Pause flow execution for a specified duration.
 *
 * In sync mode: Uses sleep() to pause.
 * In async mode: Pauses the flow and schedules resume via queue.
 */
class Delay extends Action
{
    public function name(): string
    {
        return 'Delay';
    }

    public function description(): string
    {
        return 'Pause flow execution for a specified duration.';
    }

    public function icon(): string
    {
        return 'clock';
    }

    public function category(): string
    {
        return 'Flow Control';
    }

    public function fields(): array
    {
        return [
            Number::make('duration')
                ->label('Duration')
                ->description('How long to wait')
                ->default(5)
                ->min(1)
                ->required(),

            Select::make('unit')
                ->label('Time Unit')
                ->options([
                    'seconds' => 'Seconds',
                    'minutes' => 'Minutes',
                    'hours' => 'Hours',
                ])
                ->default('seconds')
                ->required(),

            Select::make('mode')
                ->label('Delay Mode')
                ->options([
                    'sync' => 'Synchronous (block execution)',
                    'pause' => 'Pause flow (resume later)',
                ])
                ->default('sync')
                ->description('Sync blocks the process; Pause schedules a resume'),

            Number::make('max_sync_seconds')
                ->label('Max Sync Delay (seconds)')
                ->description('Maximum duration for sync mode (safety limit)')
                ->default(30)
                ->min(1)
                ->max(300),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $duration = (int) $this->config('duration', 5);
        $unit = $this->config('unit', 'seconds');
        $mode = $this->config('mode', 'sync');
        $maxSyncSeconds = (int) $this->config('max_sync_seconds', 30);

        // Convert to seconds
        $seconds = match ($unit) {
            'minutes' => $duration * 60,
            'hours' => $duration * 3600,
            default => $duration,
        };

        $context->log('info', "Delay: Waiting {$duration} {$unit} ({$seconds}s) in {$mode} mode");

        if ($mode === 'sync') {
            // Safety limit for sync mode
            $actualDelay = min($seconds, $maxSyncSeconds);

            if ($actualDelay < $seconds) {
                $context->log('warning', "Delay: Duration capped to {$maxSyncSeconds}s safety limit");
            }

            // Store delay info
            $context->set('delay_requested', $seconds);
            $context->set('delay_actual', $actualDelay);
            $context->set('delay_started_at', now()->toIso8601String());

            // Block execution
            sleep($actualDelay);

            $context->set('delay_completed_at', now()->toIso8601String());
            $context->log('info', "Delay: Completed after {$actualDelay}s");
        } else {
            // Pause mode - schedule resume
            $context->set('delay_seconds', $seconds);
            $context->set('delay_resume_at', now()->addSeconds($seconds)->toIso8601String());

            // Tell the flow runner to pause and schedule resume
            $context->pause($seconds);

            $context->log('info', "Delay: Flow paused, will resume in {$seconds}s");
        }

        return $context;
    }
}
