<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

use DateTimeZone;

class TimezoneSelect extends Field
{
    protected bool $searchable = true;

    protected bool $grouped = true;

    public function type(): string
    {
        return 'timezone-select';
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function grouped(bool $grouped = true): static
    {
        $this->grouped = $grouped;

        return $this;
    }

    /**
     * Get all available timezones grouped by region
     */
    public function getTimezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        if (! $this->grouped) {
            return array_combine($timezones, $timezones);
        }

        $grouped = [];
        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone, 2);
            $region = $parts[0];
            $city = $parts[1] ?? $timezone;

            if (! isset($grouped[$region])) {
                $grouped[$region] = [];
            }

            $grouped[$region][$timezone] = str_replace('_', ' ', $city);
        }

        return $grouped;
    }

    /**
     * Get common timezones for quick selection
     */
    public function getCommonTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'Europe/London' => 'London (GMT)',
            'Europe/Paris' => 'Paris (CET)',
            'Europe/Berlin' => 'Berlin (CET)',
            'Europe/Brussels' => 'Brussels (CET)',
            'America/New_York' => 'New York (EST)',
            'America/Chicago' => 'Chicago (CST)',
            'America/Denver' => 'Denver (MST)',
            'America/Los_Angeles' => 'Los Angeles (PST)',
            'Asia/Tokyo' => 'Tokyo (JST)',
            'Asia/Shanghai' => 'Shanghai (CST)',
            'Asia/Dubai' => 'Dubai (GST)',
            'Australia/Sydney' => 'Sydney (AEST)',
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'timezones' => $this->getTimezones(),
            'common' => $this->getCommonTimezones(),
            'searchable' => $this->searchable,
            'grouped' => $this->grouped,
        ]);
    }
}
