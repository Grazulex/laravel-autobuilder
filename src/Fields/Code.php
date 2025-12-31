<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class Code extends Field
{
    protected string $language = 'php';

    protected int $height = 200;

    public function type(): string
    {
        return 'code';
    }

    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'language' => $this->language,
            'height' => $this->height,
        ]);
    }
}
