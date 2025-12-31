<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class FieldMatchesRegex extends Condition
{
    public function name(): string
    {
        return 'Field Matches Regex';
    }

    public function description(): string
    {
        return 'Checks if a field matches a regular expression pattern.';
    }

    public function icon(): string
    {
        return 'regex';
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

            Text::make('pattern')
                ->label('Regex Pattern')
                ->placeholder('/^[a-z]+$/i')
                ->description('Include delimiters and flags')
                ->required(),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $value = (string) $context->get($this->config('field'), '');
        $pattern = $this->config('pattern');

        if (empty($pattern)) {
            return false;
        }

        // Suppress errors for invalid regex
        $result = @preg_match($pattern, $value);

        return $result === 1;
    }
}
