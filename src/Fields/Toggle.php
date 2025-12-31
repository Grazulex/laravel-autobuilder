<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class Toggle extends Field
{
    protected string $onLabel = 'Yes';

    protected string $offLabel = 'No';

    public function type(): string
    {
        return 'toggle';
    }

    public function onLabel(string $label): static
    {
        $this->onLabel = $label;

        return $this;
    }

    public function offLabel(string $label): static
    {
        $this->offLabel = $label;

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'onLabel' => $this->onLabel,
            'offLabel' => $this->offLabel,
        ]);
    }
}
