<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class Textarea extends Field
{
    protected int $rows = 4;

    protected ?int $maxLength = null;

    public function type(): string
    {
        return 'textarea';
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;

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
            'rows' => $this->rows,
            'maxLength' => $this->maxLength,
        ]);
    }
}
