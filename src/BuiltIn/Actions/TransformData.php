<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Transform Data - Apply transformations to collections or arrays.
 *
 * Supports pluck, filter, map, sort, unique, flatten, and more.
 */
class TransformData extends Action
{
    public function name(): string
    {
        return 'Transform Data';
    }

    public function description(): string
    {
        return 'Apply transformations to arrays or collections (pluck, filter, sort, etc.)';
    }

    public function icon(): string
    {
        return 'shuffle';
    }

    public function category(): string
    {
        return 'Data';
    }

    public function fields(): array
    {
        return [
            Text::make('source')
                ->label('Source Field')
                ->description('Field containing the array/collection to transform')
                ->placeholder('users')
                ->supportsVariables()
                ->required(),

            Select::make('operation')
                ->label('Operation')
                ->options([
                    'pluck' => 'Pluck - Extract single field',
                    'filter_not_empty' => 'Filter - Remove empty values',
                    'filter_by_field' => 'Filter - By field value',
                    'sort_asc' => 'Sort - Ascending',
                    'sort_desc' => 'Sort - Descending',
                    'sort_by_field' => 'Sort - By field',
                    'unique' => 'Unique - Remove duplicates',
                    'flatten' => 'Flatten - Reduce nesting',
                    'reverse' => 'Reverse - Reverse order',
                    'take' => 'Take - First N items',
                    'skip' => 'Skip - Skip first N items',
                    'count' => 'Count - Get count',
                    'sum' => 'Sum - Sum values',
                    'avg' => 'Average - Calculate average',
                    'min' => 'Min - Get minimum',
                    'max' => 'Max - Get maximum',
                    'first' => 'First - Get first item',
                    'last' => 'Last - Get last item',
                    'keys' => 'Keys - Get array keys',
                    'values' => 'Values - Get array values',
                    'implode' => 'Implode - Join to string',
                ])
                ->default('pluck')
                ->required(),

            Text::make('field')
                ->label('Field')
                ->description('Field to pluck, sort by, or filter by')
                ->placeholder('name'),

            Text::make('value')
                ->label('Value')
                ->description('Value to filter by or delimiter for implode')
                ->supportsVariables(),

            Text::make('amount')
                ->label('Amount')
                ->description('Number of items for take/skip')
                ->default('10'),

            Text::make('store_as')
                ->label('Store Result As')
                ->description('Variable name to store the transformed data')
                ->default('transformed_data')
                ->required(),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $sourcePath = $this->resolveValue($this->config('source'), $context);
        $operation = $this->config('operation', 'pluck');
        $field = $this->config('field', '');
        $value = $this->resolveValue($this->config('value', ''), $context);
        $amount = (int) $this->config('amount', 10);
        $storeAs = $this->config('store_as', 'transformed_data');

        // Get the source data
        $data = $context->get($sourcePath);

        if ($data === null) {
            $context->log('warning', "TransformData: Source '{$sourcePath}' is null");
            $context->set($storeAs, null);

            return $context;
        }

        // Convert to collection
        $collection = Collection::wrap($data);

        // Apply transformation
        $result = match ($operation) {
            'pluck' => $collection->pluck($field)->all(),
            'filter_not_empty' => $collection->filter()->values()->all(),
            'filter_by_field' => $collection->where($field, $value)->values()->all(),
            'sort_asc' => $collection->sort()->values()->all(),
            'sort_desc' => $collection->sortDesc()->values()->all(),
            'sort_by_field' => $collection->sortBy($field)->values()->all(),
            'unique' => $field ? $collection->unique($field)->values()->all() : $collection->unique()->values()->all(),
            'flatten' => $collection->flatten()->all(),
            'reverse' => $collection->reverse()->values()->all(),
            'take' => $collection->take($amount)->all(),
            'skip' => $collection->skip($amount)->values()->all(),
            'count' => $collection->count(),
            'sum' => $field ? $collection->sum($field) : $collection->sum(),
            'avg' => $field ? $collection->avg($field) : $collection->avg(),
            'min' => $field ? $collection->min($field) : $collection->min(),
            'max' => $field ? $collection->max($field) : $collection->max(),
            'first' => $collection->first(),
            'last' => $collection->last(),
            'keys' => $collection->keys()->all(),
            'values' => $collection->values()->all(),
            'implode' => $field ? $collection->pluck($field)->implode($value ?: ', ') : $collection->implode($value ?: ', '),
            default => $collection->all(),
        };

        $context->set($storeAs, $result);

        $resultInfo = is_array($result) ? count($result).' items' : (is_scalar($result) ? $result : gettype($result));
        $context->log('info', "TransformData: {$operation} on {$sourcePath} -> {$storeAs} ({$resultInfo})");

        return $context;
    }
}
