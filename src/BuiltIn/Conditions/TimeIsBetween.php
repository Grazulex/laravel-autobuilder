<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Carbon\Carbon;
use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\TimezoneSelect;
use Grazulex\AutoBuilder\Flow\FlowContext;

class TimeIsBetween extends Condition
{
    public function name(): string
    {
        return 'Time Is Between';
    }

    public function description(): string
    {
        return 'Checks if current time is within a specified range.';
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
            Text::make('start_time')
                ->label('Start Time')
                ->placeholder('09:00')
                ->description('Format: HH:MM (24-hour)')
                ->required(),

            Text::make('end_time')
                ->label('End Time')
                ->placeholder('17:00')
                ->description('Format: HH:MM (24-hour)')
                ->required(),

            TimezoneSelect::make('timezone')
                ->label('Timezone')
                ->description('Timezone for time comparison')
                ->default(config('app.timezone')),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $timezone = $this->config('timezone', config('app.timezone'));
        $now = now()->timezone($timezone);

        $startTime = Carbon::parse($this->config('start_time'), $timezone)->setDateFrom($now);
        $endTime = Carbon::parse($this->config('end_time'), $timezone)->setDateFrom($now);

        // Handle overnight ranges (e.g., 22:00 to 06:00)
        if ($endTime->lt($startTime)) {
            return $now->gte($startTime) || $now->lte($endTime);
        }

        return $now->between($startTime, $endTime);
    }

    public function onTrueLabel(): string
    {
        return 'In Range';
    }

    public function onFalseLabel(): string
    {
        return 'Outside Range';
    }
}
