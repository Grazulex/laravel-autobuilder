<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class KeyValue extends Field
{
    protected string $keyLabel = 'Key';

    protected string $valueLabel = 'Value';

    protected ?int $maxPairs = null;

    public function type(): string
    {
        return 'keyvalue';
    }

    public function keyLabel(string $label): static
    {
        $this->keyLabel = $label;

        return $this;
    }

    public function valueLabel(string $label): static
    {
        $this->valueLabel = $label;

        return $this;
    }

    public function maxPairs(int $max): static
    {
        $this->maxPairs = $max;

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'keyLabel' => $this->keyLabel,
            'valueLabel' => $this->valueLabel,
            'maxPairs' => $this->maxPairs,
        ]);
    }
}
