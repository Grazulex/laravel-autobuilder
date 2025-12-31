<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\ModelSelect;
use Grazulex\AutoBuilder\Fields\Toggle;

class OnModelDeleted extends Trigger
{
    public function name(): string
    {
        return 'Model Deleted';
    }

    public function description(): string
    {
        return 'Triggers when a model record is deleted from the database.';
    }

    public function icon(): string
    {
        return 'database-x';
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

            Toggle::make('include_soft_deletes')
                ->label('Include soft deletes')
                ->description('Trigger on soft deletes as well')
                ->default(true),
        ];
    }

    public function register(): void
    {
        $modelClass = $this->config('model');
        $includeSoftDeletes = $this->config('include_soft_deletes', true);

        if (! $modelClass || ! class_exists($modelClass)) {
            return;
        }

        $modelClass::deleted(function ($model) use ($includeSoftDeletes) {
            $isSoftDelete = method_exists($model, 'trashed') && $model->trashed();

            if ($isSoftDelete && ! $includeSoftDeletes) {
                return;
            }

            $this->dispatch([
                'model' => $model->toArray(),
                'model_class' => get_class($model),
                'model_id' => $model->getKey(),
                'soft_deleted' => $isSoftDelete,
            ]);
        });
    }

    public function samplePayload(): array
    {
        return [
            'model' => ['id' => 1, 'name' => 'Deleted Record'],
            'model_class' => 'App\\Models\\User',
            'model_id' => 1,
            'soft_deleted' => false,
        ];
    }
}
