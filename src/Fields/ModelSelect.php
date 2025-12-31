<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Fields;

use Illuminate\Support\Facades\File;

class ModelSelect extends Field
{
    protected array $filters = [];

    protected bool $searchable = true;

    public function type(): string
    {
        return 'model-select';
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Discover all Eloquent models in the application
     */
    public function getModels(): array
    {
        $models = [];
        $modelsPath = app_path('Models');

        if (File::isDirectory($modelsPath)) {
            $files = File::allFiles($modelsPath);

            foreach ($files as $file) {
                $namespace = 'App\\Models\\';
                $className = $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $file->getRelativePathname()
                );

                if (class_exists($className)) {
                    $models[$className] = class_basename($className);
                }
            }
        }

        return $models;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'models' => $this->getModels(),
            'filters' => $this->filters,
            'searchable' => $this->searchable,
        ]);
    }
}
