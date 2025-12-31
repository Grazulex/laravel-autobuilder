<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\TimezoneSelect;

class OnSchedule extends Trigger
{
    public function name(): string
    {
        return 'Scheduled';
    }

    public function description(): string
    {
        return 'Triggers on a cron schedule.';
    }

    public function icon(): string
    {
        return 'clock';
    }

    public function category(): string
    {
        return 'Time';
    }

    public function fields(): array
    {
        return [
            Select::make('frequency')
                ->label('Frequency')
                ->options([
                    'everyMinute' => 'Every Minute',
                    'everyFiveMinutes' => 'Every 5 Minutes',
                    'everyTenMinutes' => 'Every 10 Minutes',
                    'everyFifteenMinutes' => 'Every 15 Minutes',
                    'everyThirtyMinutes' => 'Every 30 Minutes',
                    'hourly' => 'Every Hour',
                    'daily' => 'Every Day (at midnight)',
                    'dailyAt' => 'Every Day at specific time',
                    'weekly' => 'Every Week (Sunday)',
                    'monthly' => 'Every Month (1st)',
                    'custom' => 'Custom Cron Expression',
                ])
                ->required(),

            Text::make('time')
                ->label('Time (HH:MM)')
                ->placeholder('09:00')
                ->description('For "Every Day at specific time"')
                ->visibleWhen('frequency', 'dailyAt'),

            Text::make('cron')
                ->label('Cron Expression')
                ->placeholder('* * * * *')
                ->description('Standard cron format: minute hour day month weekday')
                ->visibleWhen('frequency', 'custom'),

            TimezoneSelect::make('timezone')
                ->label('Timezone')
                ->description('Timezone for schedule evaluation')
                ->default(config('app.timezone')),
        ];
    }

    public function register(): void
    {
        // Handled by the scheduler in the service provider
    }

    public function getCronExpression(): string
    {
        $frequency = $this->config('frequency');

        if ($frequency === 'custom') {
            return $this->config('cron', '* * * * *');
        }

        if ($frequency === 'dailyAt') {
            $time = $this->config('time', '00:00');
            [$hour, $minute] = explode(':', $time);

            return "{$minute} {$hour} * * *";
        }

        return match ($frequency) {
            'everyMinute' => '* * * * *',
            'everyFiveMinutes' => '*/5 * * * *',
            'everyTenMinutes' => '*/10 * * * *',
            'everyFifteenMinutes' => '*/15 * * * *',
            'everyThirtyMinutes' => '*/30 * * * *',
            'hourly' => '0 * * * *',
            'daily' => '0 0 * * *',
            'weekly' => '0 0 * * 0',
            'monthly' => '0 0 1 * *',
            default => '* * * * *',
        };
    }

    public function samplePayload(): array
    {
        return [
            'scheduled_at' => now()->toIso8601String(),
            'timezone' => $this->config('timezone', config('app.timezone')),
            'frequency' => $this->config('frequency'),
        ];
    }
}
