<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Traits\RendersVariables;

abstract class Brick
{
    use RendersVariables;

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get the display name of the brick
     */
    abstract public function name(): string;

    /**
     * Get the description of what this brick does
     */
    abstract public function description(): string;

    /**
     * Get the icon name (Lucide icon)
     */
    abstract public function icon(): string;

    /**
     * Get the category for grouping in the UI
     */
    public function category(): string
    {
        return 'General';
    }

    /**
     * Get the fields configuration for this brick
     *
     * @return array<\Grazulex\AutoBuilder\Fields\Field>
     */
    abstract public function fields(): array;

    /**
     * Validate the brick configuration
     */
    public function validate(): array
    {
        $errors = [];

        foreach ($this->fields() as $field) {
            if ($field->isRequired() && empty($this->config($field->getName()))) {
                $errors[] = "Field '{$field->getLabel()}' is required.";
            }

            $fieldErrors = $field->validate($this->config($field->getName()));
            $errors = array_merge($errors, $fieldErrors);
        }

        return $errors;
    }

    /**
     * Get a configuration value
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get all configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set configuration
     */
    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Configure the brick (alias for setConfig)
     */
    public function configure(array $config): static
    {
        return $this->setConfig($config);
    }

    /**
     * Get the brick type (trigger, condition, action)
     */
    abstract public function type(): string;

    /**
     * Get a sample payload for testing
     */
    public function samplePayload(): array
    {
        return [];
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'class' => static::class,
            'type' => $this->type(),
            'name' => $this->name(),
            'description' => $this->description(),
            'icon' => $this->icon(),
            'category' => $this->category(),
            'fields' => array_map(fn ($field) => $field->toArray(), $this->fields()),
        ];
    }
}
