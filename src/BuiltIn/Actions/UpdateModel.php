<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\ModelSelect;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Database\Eloquent\Model;

class UpdateModel extends Action
{
    public function name(): string
    {
        return 'Update Model';
    }

    public function description(): string
    {
        return 'Updates an existing Eloquent model.';
    }

    public function icon(): string
    {
        return 'edit';
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
                ->description('Field in payload containing the model (e.g., order, user)')
                ->supportsVariables()
                ->required(),

            ModelSelect::make('model_class')
                ->label('Model Class')
                ->description('Required if model_field contains an ID instead of model'),

            KeyValue::make('attributes')
                ->label('Attributes to Update')
                ->description('Attribute values to update')
                ->supportsVariables()
                ->required(),

            Text::make('store_as')
                ->label('Store Result As')
                ->description('Variable name to store the updated model')
                ->default('updated_model'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $modelField = $this->config('model_field');
        $modelClass = $this->config('model_class');
        $attributes = $this->config('attributes', []);
        $storeAs = $this->config('store_as', 'updated_model');

        // Handle JSON string from frontend
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true) ?? [];
        }

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
            $context->log('error', "UpdateModel: Could not resolve model from '{$modelField}'");

            return $context;
        }

        // Store original for rollback
        $context->set("{$storeAs}_original", $model->getAttributes());

        // Resolve variables in attributes
        $resolvedAttributes = [];
        foreach ($attributes as $key => $value) {
            $resolvedAttributes[$key] = $this->resolveValue($value, $context);
        }

        // Check if model has proper mass assignment protection
        if (! $this->hasMassAssignmentProtection($model)) {
            $context->log('warning', 'UpdateModel: Model has no mass assignment protection. Consider adding $fillable or $guarded.');
        }

        // Filter attributes to only fillable ones for safety
        $safeAttributes = $this->filterFillableAttributes($model, $resolvedAttributes);

        // Use fill() + save() for explicit control
        $model->fill($safeAttributes);
        $model->save();

        $context->set($storeAs, $model->fresh());

        $context->log('info', 'Updated model with ID: '.$model->getKey());

        return $context;
    }

    public function rollback(FlowContext $context): void
    {
        $storeAs = $this->config('store_as', 'updated_model');
        $model = $context->get($storeAs);
        $original = $context->get("{$storeAs}_original");

        if ($model && $original) {
            $model->fill($original);
            $model->save();
            $context->log('info', 'Rolled back: restored original model attributes');
        }
    }

    /**
     * Check if model has mass assignment protection configured.
     */
    protected function hasMassAssignmentProtection(Model $model): bool
    {
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        // Model is protected if it has fillable attributes defined
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
}
