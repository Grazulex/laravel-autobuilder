<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

class Number extends Field
{
    protected ?float $min = null;

    protected ?float $max = null;

    protected float $step = 1;

    public function type(): string
    {
        return 'number';
    }

    public function min(float $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(float $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(float $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function validate(mixed $value): array
    {
        $errors = parent::validate($value);

        if ($value !== null && $value !== '') {
            if (! is_numeric($value)) {
                $errors[] = "The {$this->label} field must be a number.";
            } else {
                if ($this->min !== null && $value < $this->min) {
                    $errors[] = "The {$this->label} field must be at least {$this->min}.";
                }
                if ($this->max !== null && $value > $this->max) {
                    $errors[] = "The {$this->label} field must not exceed {$this->max}.";
                }
            }
        }

        return $errors;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ]);
    }
}
