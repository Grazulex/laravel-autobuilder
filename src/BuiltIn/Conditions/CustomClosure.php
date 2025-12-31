<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Code;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class CustomClosure extends Condition
{
    public function name(): string
    {
        return 'Custom Closure';
    }

    public function description(): string
    {
        return 'Evaluates a custom PHP closure for complex conditions.';
    }

    public function icon(): string
    {
        return 'code';
    }

    public function category(): string
    {
        return 'Advanced';
    }

    public function fields(): array
    {
        return [
            Code::make('closure')
                ->label('Closure Code')
                ->language('php')
                ->description('Return true or false. Available: $context, $payload')
                ->placeholder('return $payload[\'status\'] === \'active\' && $payload[\'amount\'] > 100;')
                ->required(),

            Text::make('description')
                ->label('Description')
                ->description('Describe what this condition checks')
                ->placeholder('Check if order is active and above threshold'),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $closureCode = $this->config('closure');

        if (empty($closureCode)) {
            return false;
        }

        $payload = $context->all();

        try {
            // Create the closure from the code
            $closure = eval("return function(\$context, \$payload) { {$closureCode} };");

            if (! is_callable($closure)) {
                $context->log('warning', 'CustomClosure: Invalid closure code');

                return false;
            }

            $result = $closure($context, $payload);

            return (bool) $result;
        } catch (\Throwable $e) {
            $context->log('error', 'CustomClosure evaluation failed: '.$e->getMessage());

            return false;
        }
    }

    public function onTrueLabel(): string
    {
        return 'Condition Met';
    }

    public function onFalseLabel(): string
    {
        return 'Condition Not Met';
    }
}
