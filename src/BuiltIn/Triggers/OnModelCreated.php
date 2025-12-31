<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\ModelSelect;

class OnModelCreated extends Trigger
{
    public function name(): string
    {
        return 'Model Created';
    }

    public function description(): string
    {
        return 'Triggers when a new model record is created in the database.';
    }

    public function icon(): string
    {
        return 'database-plus';
    }

    public function category(): string
    {
        return 'Database';
    }

    public function fields(): array
    {
        return [
            ModelSelect::make('model')
                ->label('Model')
                ->description('Select the model to watch')
                ->required(),
        ];
    }

    public function register(): void
    {
        $modelClass = $this->config('model');

        if (! $modelClass || ! class_exists($modelClass)) {
            return;
        }

        $modelClass::created(function ($model) {
            $this->dispatch([
                'model' => $model->toArray(),
                'model_class' => get_class($model),
                'model_id' => $model->getKey(),
            ]);
        });
    }

    public function samplePayload(): array
    {
        return [
            'model' => ['id' => 1, 'name' => 'Example', 'created_at' => now()->toIso8601String()],
            'model_class' => 'App\\Models\\User',
            'model_id' => 1,
        ];
    }
}
