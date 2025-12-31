<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\ModelSelect;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class CreateModel extends Action
{
    public function name(): string
    {
        return 'Create Model';
    }

    public function description(): string
    {
        return 'Creates a new Eloquent model instance.';
    }

    public function icon(): string
    {
        return 'plus-circle';
    }

    public function category(): string
    {
        return 'Database';
    }

    public function fields(): array
    {
        return [
            ModelSelect::make('model')
                ->label('Model Class')
                ->description('Select the model to create')
                ->required(),

            KeyValue::make('attributes')
                ->label('Attributes')
                ->description('Model attributes to set')
                ->supportsVariables()
                ->required(),

            Text::make('store_as')
                ->label('Store Result As')
                ->description('Variable name to store the created model')
                ->default('created_model'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $modelClass = $this->config('model');
        $attributes = $this->config('attributes', []);
        $storeAs = $this->config('store_as', 'created_model');

        // Handle JSON string from frontend
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true) ?? [];
        }

        if (! class_exists($modelClass)) {
            $context->log('error', "CreateModel: Model class '{$modelClass}' not found");

            return $context;
        }

        // Resolve variables in attributes
        $resolvedAttributes = [];
        foreach ($attributes as $key => $value) {
            $resolvedAttributes[$key] = $this->resolveValue($value, $context);
        }

        $model = $modelClass::create($resolvedAttributes);

        $context->set($storeAs, $model);
        $context->set("{$storeAs}_id", $model->getKey());

        $context->log('info', "Created {$modelClass} with ID: {$model->getKey()}");

        return $context;
    }

    public function rollback(FlowContext $context): void
    {
        $storeAs = $this->config('store_as', 'created_model');
        $model = $context->get($storeAs);

        if ($model && method_exists($model, 'delete')) {
            $model->delete();
            $context->log('info', 'Rolled back: deleted created model');
        }
    }
}
