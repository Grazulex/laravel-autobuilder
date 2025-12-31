<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class Text extends Field
{
    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected ?int $maxLength = null;

    public function type(): string
    {
        return 'text';
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'maxLength' => $this->maxLength,
        ]);
    }
}
