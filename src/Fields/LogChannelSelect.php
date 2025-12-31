<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class LogChannelSelect extends Field
{
    protected bool $allowEmpty = true;

    public function type(): string
    {
        return 'log-channel-select';
    }

    public function allowEmpty(bool $allowEmpty = true): static
    {
        $this->allowEmpty = $allowEmpty;

        return $this;
    }

    /**
     * Get all configured log channels from Laravel config
     */
    public function getChannels(): array
    {
        $channels = [];

        if ($this->allowEmpty) {
            $channels[''] = 'Default Channel';
        }

        $configuredChannels = config('logging.channels', []);

        foreach ($configuredChannels as $name => $config) {
            $driver = $config['driver'] ?? 'unknown';
            $channels[$name] = ucfirst($name) . " ({$driver})";
        }

        return $channels;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'channels' => $this->getChannels(),
            'allowEmpty' => $this->allowEmpty,
        ]);
    }
}
