<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Database\Eloquent\Model;

class DeleteModel extends Action
{
    public function name(): string
    {
        return 'Delete Model';
    }

    public function description(): string
    {
        return 'Deletes an Eloquent model (soft delete if available).';
    }

    public function icon(): string
    {
        return 'trash-2';
    }

    public function category(): string
    {
        return 'Database';
    }

    public function fields(): array
    {
        return [
            Text::make('model_field')
                ->label('Model Field')
                ->description('Field in payload containing the model')
                ->supportsVariables()
                ->required(),

            Text::make('model_class')
                ->label('Model Class')
                ->placeholder('App\\Models\\Order')
                ->description('Required if model_field contains an ID'),

            Toggle::make('force_delete')
                ->label('Force Delete')
                ->description('Permanently delete (bypass soft delete)')
                ->default(false),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $modelField = $this->config('model_field');
        $modelClass = $this->config('model_class');
        $forceDelete = $this->config('force_delete', false);

        $modelData = $context->get($modelField);

        // Resolve the model
        $model = null;
        if ($modelData instanceof Model) {
            $model = $modelData;
        } elseif ($modelClass && class_exists($modelClass)) {
            if (is_numeric($modelData)) {
                $model = $modelClass::find($modelData);
            } elseif (is_array($modelData) && isset($modelData['id'])) {
                $model = $modelClass::find($modelData['id']);
            }
        }

        if (! $model) {
            $context->log('warning', "DeleteModel: Model not found at '{$modelField}'");

            return $context;
        }

        // Store for potential rollback
        $context->set('_deleted_model_data', [
            'class' => get_class($model),
            'attributes' => $model->getAttributes(),
        ]);

        if ($forceDelete && method_exists($model, 'forceDelete')) {
            $model->forceDelete();
            $context->log('info', 'Force deleted model: '.get_class($model));
        } else {
            $model->delete();
            $context->log('info', 'Deleted model: '.get_class($model));
        }

        return $context;
    }

    public function rollback(FlowContext $context): void
    {
        $deletedData = $context->get('_deleted_model_data');

        if (! $deletedData) {
            return;
        }

        $class = $deletedData['class'];
        $attributes = $deletedData['attributes'];

        // Try to restore if soft deleted
        if (method_exists($class, 'withTrashed')) {
            $model = $class::withTrashed()->find($attributes['id'] ?? null);
            if ($model && method_exists($model, 'restore')) {
                $model->restore();
                $context->log('info', 'Rolled back: restored soft-deleted model');

                return;
            }
        }

        // Otherwise, recreate the model
        $class::create($attributes);
        $context->log('info', 'Rolled back: recreated deleted model');
    }
}
