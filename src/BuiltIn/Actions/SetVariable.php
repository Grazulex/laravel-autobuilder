<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class SetVariable extends Action
{
    public function name(): string
    {
        return 'Set Variable';
    }

    public function description(): string
    {
        return 'Sets one or more variables in the flow context.';
    }

    public function icon(): string
    {
        return 'variable';
    }

    public function category(): string
    {
        return 'Flow Control';
    }

    public function fields(): array
    {
        return [
            Select::make('mode')
                ->label('Mode')
                ->options([
                    'single' => 'Single Variable',
                    'multiple' => 'Multiple Variables',
                ])
                ->default('single'),

            Text::make('variable_name')
                ->label('Variable Name')
                ->description('Name of the variable to set')
                ->required()
                ->visibleWhen('mode', 'single'),

            Text::make('variable_value')
                ->label('Variable Value')
                ->supportsVariables()
                ->description('Value to assign (supports templates)')
                ->visibleWhen('mode', 'single'),

            KeyValue::make('variables')
                ->label('Variables')
                ->description('Multiple variables to set')
                ->supportsVariables()
                ->visibleWhen('mode', 'multiple'),

            Select::make('value_type')
                ->label('Value Type')
                ->options([
                    'string' => 'String',
                    'integer' => 'Integer',
                    'float' => 'Float',
                    'boolean' => 'Boolean',
                    'json' => 'JSON (parse to array)',
                    'auto' => 'Auto-detect',
                ])
                ->default('auto'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $mode = $this->config('mode', 'single');
        $valueType = $this->config('value_type', 'auto');

        if ($mode === 'single') {
            $name = $this->config('variable_name');
            $value = $this->resolveValue($this->config('variable_value', ''), $context);

            $value = $this->castValue($value, $valueType);
            $context->set($name, $value);

            $context->log('info', "Variable set: {$name}");
        } else {
            $variables = $this->config('variables', []);

            foreach ($variables as $name => $value) {
                $resolvedValue = $this->resolveValue($value, $context);
                $resolvedValue = $this->castValue($resolvedValue, $valueType);
                $context->set($name, $resolvedValue);
            }

            $context->log('info', 'Multiple variables set: '.implode(', ', array_keys($variables)));
        }

        return $context;
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true) ?? $value,
            'string' => (string) $value,
            default => $this->autoDetect($value),
        };
    }

    private function autoDetect(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return str_contains((string) $value, '.') ? (float) $value : (int) $value;
        }

        if (in_array(strtolower((string) $value), ['true', 'false'], true)) {
            return strtolower((string) $value) === 'true';
        }

        if (is_string($value) && str_starts_with($value, '{') || str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}
