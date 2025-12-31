<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class DayOfWeek extends Condition
{
    public function name(): string
    {
        return 'Day of Week';
    }

    public function description(): string
    {
        return 'Checks if today is a specific day of the week.';
    }

    public function icon(): string
    {
        return 'calendar-days';
    }

    public function category(): string
    {
        return 'Time';
    }

    public function fields(): array
    {
        return [
            Select::make('days')
                ->label('Days')
                ->options([
                    '0' => 'Sunday',
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                ])
                ->multiple()
                ->required(),

            Text::make('timezone')
                ->label('Timezone')
                ->placeholder('UTC')
                ->default(config('app.timezone')),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $timezone = $this->config('timezone', config('app.timezone'));
        $currentDay = (string) now()->timezone($timezone)->dayOfWeek;
        $selectedDays = (array) $this->config('days', []);

        return in_array($currentDay, $selectedDays);
    }

    public function onTrueLabel(): string
    {
        return 'Matching Day';
    }

    public function onFalseLabel(): string
    {
        return 'Other Day';
    }
}
