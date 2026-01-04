<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class StopFlow extends Action
{
    public function name(): string
    {
        return 'Stop Flow';
    }

    public function description(): string
    {
        return 'Stops flow execution immediately with optional status.';
    }

    public function icon(): string
    {
        return 'stop-circle';
    }

    public function category(): string
    {
        return 'Flow Control';
    }

    public function fields(): array
    {
        return [
            Select::make('stop_type')
                ->label('Stop Type')
                ->options([
                    'complete' => 'Complete Successfully',
                    'fail' => 'Mark as Failed',
                    'cancel' => 'Cancel (no error)',
                ])
                ->default('complete'),

            Text::make('reason')
                ->label('Reason')
                ->supportsVariables()
                ->description('Reason for stopping the flow')
                ->placeholder('Validation failed: {{ error_message }}'),

            Text::make('output_variable')
                ->label('Final Output Variable')
                ->description('Variable to use as final flow output')
                ->placeholder('result'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $stopType = $this->config('stop_type', 'complete');
        $reason = $this->resolveValue($this->config('reason', ''), $context);
        $outputVariable = $this->config('output_variable');

        // Set the final output if specified
        if ($outputVariable) {
            $output = $context->get($outputVariable);
            $context->set('_flow_output', $output);
        }

        $context->set('_stop_requested', true);
        $context->set('_stop_type', $stopType);
        $context->set('_stop_reason', $reason);

        match ($stopType) {
            'fail' => $context->log('error', "Flow stopped with failure: {$reason}"),
            'cancel' => $context->log('warning', "Flow cancelled: {$reason}"),
            default => $context->log('info', "Flow completed: {$reason}"),
        };

        return $context;
    }
}
