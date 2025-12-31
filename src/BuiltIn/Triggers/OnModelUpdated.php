<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\ModelSelect;
use Grazulex\AutoBuilder\Fields\Select;

class OnModelUpdated extends Trigger
{
    public function name(): string
    {
        return 'Model Updated';
    }

    public function description(): string
    {
        return 'Triggers when a model record is updated in the database.';
    }

    public function icon(): string
    {
        return 'database-edit';
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

            Select::make('watch_fields')
                ->label('Watch specific fields (optional)')
                ->description('Leave empty to trigger on any change')
                ->multiple()
                ->options([]),
        ];
    }

    public function register(): void
    {
        $modelClass = $this->config('model');
        $watchFields = $this->config('watch_fields', []);

        if (! $modelClass || ! class_exists($modelClass)) {
            return;
        }

        $modelClass::updated(function ($model) use ($watchFields) {
            if (! empty($watchFields)) {
                $changedFields = array_keys($model->getChanges());
                if (empty(array_intersect($watchFields, $changedFields))) {
                    return;
                }
            }

            $this->dispatch([
                'model' => $model->toArray(),
                'original' => $model->getOriginal(),
                'changes' => $model->getChanges(),
                'model_class' => get_class($model),
                'model_id' => $model->getKey(),
            ]);
        });
    }

    public function samplePayload(): array
    {
        return [
            'model' => ['id' => 1, 'name' => 'Updated Name'],
            'original' => ['id' => 1, 'name' => 'Original Name'],
            'changes' => ['name' => 'Updated Name'],
            'model_class' => 'App\\Models\\User',
            'model_id' => 1,
        ];
    }
}
