<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Code;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class ExecuteCode extends Action
{
    public function name(): string
    {
        return 'Execute Code';
    }

    public function description(): string
    {
        return 'Executes custom PHP code within the flow.';
    }

    public function icon(): string
    {
        return 'terminal';
    }

    public function category(): string
    {
        return 'Advanced';
    }

    public function fields(): array
    {
        return [
            Code::make('code')
                ->label('PHP Code')
                ->language('php')
                ->description('Code to execute. Available: $context, $payload. Return value is stored.')
                ->placeholder('$total = $payload[\'quantity\'] * $payload[\'price\'];
return [\'total\' => $total];')
                ->required(),

            Text::make('store_result')
                ->label('Store Result As')
                ->description('Variable name to store the return value')
                ->default('code_result'),

            Text::make('description')
                ->label('Description')
                ->description('Describe what this code does')
                ->placeholder('Calculate order total'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $code = $this->config('code');
        $storeResult = $this->config('store_result', 'code_result');

        if (empty($code)) {
            return $context;
        }

        $payload = $context->all();

        try {
            $closure = eval("return function(\$context, \$payload) { {$code} };");

            if (! is_callable($closure)) {
                $context->log('error', 'ExecuteCode: Invalid code provided');

                return $context;
            }

            $result = $closure($context, $payload);

            // If result is an array, merge into context
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $context->set($key, $value);
                }
            }

            $context->set($storeResult, $result);

            $context->log('info', 'Custom code executed successfully');
        } catch (\Throwable $e) {
            $context->log('error', 'ExecuteCode failed: '.$e->getMessage());
        }

        return $context;
    }
}
