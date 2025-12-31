<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;

/**
 * SubFlow Action - Execute another flow as a subroutine.
 *
 * Allows composition and reusability of flows.
 */
class SubFlow extends Action
{
    public function name(): string
    {
        return 'Sub Flow';
    }

    public function description(): string
    {
        return 'Execute another flow as a subroutine. Great for reusability.';
    }

    public function icon(): string
    {
        return 'git-branch';
    }

    public function category(): string
    {
        return 'Flow Control';
    }

    public function fields(): array
    {
        return [
            Select::make('flow_id')
                ->label('Flow to Execute')
                ->description('Select the flow to run as a sub-flow')
                ->options($this->getAvailableFlows())
                ->required(),

            Select::make('payload_mode')
                ->label('Payload Mode')
                ->options([
                    'current' => 'Pass current context',
                    'custom' => 'Custom payload',
                    'merge' => 'Merge with custom data',
                ])
                ->default('current'),

            Text::make('custom_payload')
                ->label('Custom Payload')
                ->description('JSON object or variable path to pass as payload')
                ->supportsVariables()
                ->visibleWhen('payload_mode', 'custom'),

            Text::make('merge_data')
                ->label('Data to Merge')
                ->description('JSON object or variable path to merge with context')
                ->supportsVariables()
                ->visibleWhen('payload_mode', 'merge'),

            Toggle::make('inherit_variables')
                ->label('Inherit Variables')
                ->description('Pass current flow variables to sub-flow')
                ->default(true),

            Toggle::make('import_variables')
                ->label('Import Result Variables')
                ->description('Import variables from sub-flow result back to this flow')
                ->default(true),

            Text::make('variable_prefix')
                ->label('Variable Prefix')
                ->description('Prefix for imported variables (e.g., "subflow_")')
                ->default(''),

            Text::make('store_result_as')
                ->label('Store Result As')
                ->description('Variable name to store the sub-flow result')
                ->default('subflow_result'),

            Toggle::make('stop_on_failure')
                ->label('Stop on Failure')
                ->description('Stop parent flow if sub-flow fails')
                ->default(true),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $flowId = $this->config('flow_id');
        $payloadMode = $this->config('payload_mode', 'current');
        $inheritVariables = $this->config('inherit_variables', true);
        $importVariables = $this->config('import_variables', true);
        $variablePrefix = $this->config('variable_prefix', '');
        $storeResultAs = $this->config('store_result_as', 'subflow_result');
        $stopOnFailure = $this->config('stop_on_failure', true);

        // Load the sub-flow
        $subFlow = Flow::find($flowId);

        if (! $subFlow) {
            $context->log('error', "SubFlow: Flow not found: {$flowId}");
            if ($stopOnFailure) {
                throw new \RuntimeException("Sub-flow not found: {$flowId}");
            }

            return $context;
        }

        // Prevent infinite recursion
        if ($subFlow->id === $context->flowId) {
            $context->log('error', 'SubFlow: Cannot call self (infinite recursion prevented)');
            throw new \RuntimeException('Sub-flow cannot call itself');
        }

        // Prepare payload for sub-flow
        $payload = $this->preparePayload($context, $payloadMode);

        // Add inherited variables
        if ($inheritVariables) {
            $payload = array_merge($payload, $context->getVariables());
        }

        $context->log('info', "SubFlow: Executing '{$subFlow->name}' ({$subFlow->id})");

        // Execute sub-flow
        $runner = app(FlowRunner::class);
        $result = $runner->run($subFlow, $payload);

        // Store result
        $context->set($storeResultAs, [
            'status' => $result->status,
            'flow_id' => $subFlow->id,
            'flow_name' => $subFlow->name,
            'run_id' => $result->context->runId,
        ]);

        // Import variables from sub-flow
        if ($importVariables && $result->status === 'completed') {
            $subVariables = $result->context->getVariables();
            foreach ($subVariables as $key => $value) {
                $context->set($variablePrefix.$key, $value);
            }
        }

        // Handle failure
        if ($result->status === 'failed') {
            $context->log('error', "SubFlow: '{$subFlow->name}' failed: ".($result->error?->getMessage() ?? 'Unknown error'));

            if ($stopOnFailure) {
                throw new \RuntimeException("Sub-flow '{$subFlow->name}' failed: ".($result->error?->getMessage() ?? 'Unknown error'));
            }
        } else {
            $context->log('info', "SubFlow: '{$subFlow->name}' completed with status: {$result->status}");
        }

        return $context;
    }

    private function preparePayload(FlowContext $context, string $mode): array
    {
        return match ($mode) {
            'current' => $context->getPayload(),
            'custom' => $this->parsePayload($this->resolveValue($this->config('custom_payload', '{}'), $context)),
            'merge' => array_merge(
                $context->getPayload(),
                $this->parsePayload($this->resolveValue($this->config('merge_data', '{}'), $context))
            ),
            default => $context->getPayload(),
        };
    }

    private function parsePayload(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return [];
    }

    private function getAvailableFlows(): array
    {
        try {
            return Flow::where('active', true)
                ->pluck('name', 'id')
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
