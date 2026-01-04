<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\ModelSelect;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Database\Eloquent\Model;

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

        // Create model instance and check mass assignment protection
        /** @var Model $model */
        $model = new $modelClass();

        // Check if model has proper mass assignment protection
        if (! $this->hasMassAssignmentProtection($model)) {
            $context->log('warning', "CreateModel: Model '{$modelClass}' has no mass assignment protection. Consider adding \$fillable or \$guarded.");
        }

        // Filter attributes to only fillable ones for safety
        $safeAttributes = $this->filterFillableAttributes($model, $resolvedAttributes);

        // Use fill() + save() for explicit control
        $model->fill($safeAttributes);
        $model->save();

        $context->set($storeAs, $model);
        $context->set("{$storeAs}_id", $model->getKey());

        $context->log('info', "Created {$modelClass} with ID: {$model->getKey()}");

        return $context;
    }

    /**
     * Check if model has mass assignment protection configured.
     */
    protected function hasMassAssignmentProtection(Model $model): bool
    {
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        // Model is protected if it has fillable attributes defined
        // or if guarded is not empty (and not just ['*'])
        if (! empty($fillable)) {
            return true;
        }

        // If guarded contains '*', all attributes are guarded (protected)
        if (in_array('*', $guarded, true)) {
            return true;
        }

        // If guarded has specific fields, it's somewhat protected
        if (! empty($guarded)) {
            return true;
        }

        return false;
    }

    /**
     * Filter attributes to only include fillable ones.
     * Follows Laravel's mass assignment logic: $fillable takes priority over $guarded.
     */
    protected function filterFillableAttributes(Model $model, array $attributes): array
    {
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        // If fillable is defined, only allow those (takes priority over guarded)
        if (! empty($fillable)) {
            return array_intersect_key($attributes, array_flip($fillable));
        }

        // If guarded contains '*', no attributes are fillable
        if (in_array('*', $guarded, true)) {
            return [];
        }

        // If guarded has specific fields, filter those out
        if (! empty($guarded)) {
            return array_diff_key($attributes, array_flip($guarded));
        }

        // No fillable or guarded defined - return all (model is unprotected)
        return $attributes;
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
