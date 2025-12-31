<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;

class FieldContains extends Condition
{
    public function name(): string
    {
        return 'Field Contains';
    }

    public function description(): string
    {
        return 'Checks if a field contains a specific substring.';
    }

    public function icon(): string
    {
        return 'text-search';
    }

    public function category(): string
    {
        return 'Comparison';
    }

    public function fields(): array
    {
        return [
            Text::make('field')
                ->label('Field Path')
                ->placeholder('user.email')
                ->supportsVariables()
                ->required(),

            Text::make('needle')
                ->label('Search String')
                ->supportsVariables()
                ->required(),

            Toggle::make('case_sensitive')
                ->label('Case Sensitive')
                ->default(false),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $haystack = (string) $context->get($this->config('field'), '');
        $needle = (string) $this->resolveValue($this->config('needle'), $context);
        $caseSensitive = $this->config('case_sensitive', false);

        if (! $caseSensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return str_contains($haystack, $needle);
    }
}
