<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

abstract class Field
{
    protected string $name;

    protected string $label = '';

    protected string $description = '';

    protected mixed $default = null;

    protected bool $required = false;

    protected array $rules = [];

    protected bool $supportsVariables = false;

    protected ?string $placeholder = null;

    protected ?string $visibleWhenField = null;

    protected mixed $visibleWhenValue = null;

    protected bool $disabled = false;

    protected bool $hidden = false;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->label = str($name)->headline()->toString();
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function supportsVariables(bool $supports = true): static
    {
        $this->supportsVariables = $supports;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function visibleWhen(string $field, mixed $value): static
    {
        $this->visibleWhenField = $field;
        $this->visibleWhenValue = $value;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    abstract public function type(): string;

    public function validate(mixed $value): array
    {
        $errors = [];

        if ($this->required && ($value === null || $value === '')) {
            $errors[] = "The {$this->label} field is required.";
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type(),
            'label' => $this->label,
            'description' => $this->description,
            'default' => $this->default,
            'required' => $this->required,
            'rules' => $this->rules,
            'supportsVariables' => $this->supportsVariables,
            'placeholder' => $this->placeholder,
            'visibleWhen' => $this->visibleWhenField ? [
                'field' => $this->visibleWhenField,
                'value' => $this->visibleWhenValue,
            ] : null,
            'disabled' => $this->disabled,
            'hidden' => $this->hidden,
        ];
    }
}
