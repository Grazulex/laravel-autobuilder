<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;

class Wait extends Action
{
    public function name(): string
    {
        return 'Wait';
    }

    public function description(): string
    {
        return 'Pauses flow execution for a specified duration or until a condition.';
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
            Select::make('wait_type')
                ->label('Wait Type')
                ->options([
                    'duration' => 'Fixed Duration',
                    'until_time' => 'Until Specific Time',
                    'pause' => 'Pause (Resume Manually)',
                ])
                ->default('duration'),

            Number::make('duration')
                ->label('Duration')
                ->description('Time to wait')
                ->default(5)
                ->min(1)
                ->visibleWhen('wait_type', 'duration'),

            Select::make('duration_unit')
                ->label('Unit')
                ->options([
                    'seconds' => 'Seconds',
                    'minutes' => 'Minutes',
                    'hours' => 'Hours',
                    'days' => 'Days',
                ])
                ->default('seconds')
                ->visibleWhen('wait_type', 'duration'),

            Text::make('until_time')
                ->label('Until Time')
                ->supportsVariables()
                ->description('DateTime string (e.g., 2024-12-31 23:59:59)')
                ->placeholder('{{ scheduled_time }}')
                ->visibleWhen('wait_type', 'until_time'),

            Text::make('timezone')
                ->label('Timezone')
                ->default('UTC')
                ->visibleWhen('wait_type', 'until_time'),

            Toggle::make('async')
                ->label('Async Wait')
                ->description('Pause flow and resume via queue (recommended for long waits)')
                ->default(true),

            Text::make('pause_reason')
                ->label('Pause Reason')
                ->description('Reason for pausing (shown in dashboard)')
                ->supportsVariables()
                ->visibleWhen('wait_type', 'pause'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $waitType = $this->config('wait_type', 'duration');
        $async = $this->config('async', true);

        $resumeAt = match ($waitType) {
            'duration' => $this->calculateDurationResume(),
            'until_time' => $this->calculateUntilTimeResume($context),
            'pause' => null, // Manual resume
            default => now(),
        };

        if ($waitType === 'pause') {
            $reason = $this->resolveValue($this->config('pause_reason', 'Manual pause'), $context);
            $context->pause($reason);
            $context->log('info', "Flow paused: {$reason}");

            return $context;
        }

        if ($async && $resumeAt) {
            // Store resume time for async processing
            $context->set('_wait_resume_at', $resumeAt->toISOString());
            $context->pause("Waiting until {$resumeAt->toDateTimeString()}");
            $context->log('info', "Flow will resume at: {$resumeAt->toDateTimeString()}");

            return $context;
        }

        // Synchronous wait (blocking - use with caution)
        if ($resumeAt) {
            $seconds = max(0, $resumeAt->diffInSeconds(now()));
            if ($seconds > 0 && $seconds <= 60) {
                sleep($seconds);
                $context->log('info', "Waited {$seconds} seconds synchronously");
            } elseif ($seconds > 60) {
                $context->log('warning', 'Synchronous wait too long, consider using async');
            }
        }

        return $context;
    }

    private function calculateDurationResume(): \Carbon\Carbon
    {
        $duration = (int) $this->config('duration', 5);
        $unit = $this->config('duration_unit', 'seconds');

        return match ($unit) {
            'minutes' => now()->addMinutes($duration),
            'hours' => now()->addHours($duration),
            'days' => now()->addDays($duration),
            default => now()->addSeconds($duration),
        };
    }

    private function calculateUntilTimeResume(FlowContext $context): \Carbon\Carbon
    {
        $timeString = $this->resolveValue($this->config('until_time'), $context);
        $timezone = $this->config('timezone', 'UTC');

        return \Carbon\Carbon::parse($timeString, $timezone);
    }
}
