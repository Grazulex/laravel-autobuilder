<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

use Closure;

class Select extends Field
{
    protected array|Closure $options = [];

    protected bool $searchable = false;

    protected bool $multiple = false;

    public function type(): string
    {
        return 'select';
    }

    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getOptions(): array
    {
        if ($this->options instanceof Closure) {
            return ($this->options)();
        }

        return $this->options;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->formatOptionsForFrontend(),
            'searchable' => $this->searchable,
            'multiple' => $this->multiple,
        ]);
    }

    /**
     * Format options as array of {value, label} objects for frontend.
     *
     * Converts associative array ['key' => 'Label'] to [{value: 'key', label: 'Label'}, ...]
     */
    protected function formatOptionsForFrontend(): array
    {
        $options = $this->getOptions();
        $formatted = [];

        foreach ($options as $key => $value) {
            // If already in {value, label} format (array with 'value' key)
            if (is_array($value) && isset($value['value'])) {
                $formatted[] = $value;
            }
            // Associative array: key is value, value is label (handles both string and int keys)
            elseif (! is_array($value)) {
                $formatted[] = ['value' => (string) $key, 'label' => (string) $value];
            }
        }

        return $formatted;
    }
}
