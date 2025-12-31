<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Support\Collection;

/**
 * ForEach Action - Iterate over a collection and execute a sub-flow for each item.
 *
 * This allows batch processing within flows.
 */
class LoopEach extends Action
{
    public function name(): string
    {
        return 'For Each';
    }

    public function description(): string
    {
        return 'Iterate over a collection and execute a sub-flow for each item.';
    }

    public function icon(): string
    {
        return 'repeat';
    }

    public function category(): string
    {
        return 'Flow Control';
    }

    public function fields(): array
    {
        return [
            Text::make('collection')
                ->label('Collection')
                ->description('Field containing the array/collection to iterate')
                ->placeholder('users')
                ->supportsVariables()
                ->required(),

            Select::make('flow_id')
                ->label('Sub-Flow to Execute')
                ->description('Flow to run for each item')
                ->options($this->getAvailableFlows())
                ->required(),

            Text::make('item_variable')
                ->label('Item Variable Name')
                ->description('Variable name for current item in sub-flow')
                ->default('item')
                ->required(),

            Text::make('index_variable')
                ->label('Index Variable Name')
                ->description('Variable name for current index')
                ->default('index'),

            Toggle::make('pass_context')
                ->label('Pass Parent Context')
                ->description('Include parent flow variables in sub-flow')
                ->default(true),

            Toggle::make('collect_results')
                ->label('Collect Results')
                ->description('Gather results from each iteration')
                ->default(true),

            Text::make('results_variable')
                ->label('Results Variable')
                ->description('Variable to store collected results')
                ->default('foreach_results'),

            Toggle::make('stop_on_error')
                ->label('Stop on Error')
                ->description('Stop iteration if a sub-flow fails')
                ->default(false),

            Number::make('max_iterations')
                ->label('Max Iterations')
                ->description('Safety limit (0 = unlimited)')
                ->default(100)
                ->min(0),

            Number::make('delay_between')
                ->label('Delay Between (ms)')
                ->description('Delay between iterations in milliseconds')
                ->default(0)
                ->min(0),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $collectionPath = $this->resolveValue($this->config('collection'), $context);
        $flowId = $this->config('flow_id');
        $itemVariable = $this->config('item_variable', 'item');
        $indexVariable = $this->config('index_variable', 'index');
        $passContext = $this->config('pass_context', true);
        $collectResults = $this->config('collect_results', true);
        $resultsVariable = $this->config('results_variable', 'foreach_results');
        $stopOnError = $this->config('stop_on_error', false);
        $maxIterations = (int) $this->config('max_iterations', 100);
        $delayBetween = (int) $this->config('delay_between', 0);

        // Get the collection
        $data = $context->get($collectionPath);

        if ($data === null) {
            $context->log('warning', "ForEach: Collection '{$collectionPath}' is null");
            $context->set($resultsVariable, []);
            $context->set('foreach_count', 0);

            return $context;
        }

        $collection = Collection::wrap($data);
        $total = $collection->count();

        // Apply max iterations limit
        if ($maxIterations > 0 && $total > $maxIterations) {
            $context->log('warning', "ForEach: Collection size ({$total}) exceeds max ({$maxIterations}), truncating");
            $collection = $collection->take($maxIterations);
            $total = $maxIterations;
        }

        // Load sub-flow
        $subFlow = Flow::find($flowId);

        if (! $subFlow) {
            $context->log('error', "ForEach: Sub-flow not found: {$flowId}");
            throw new \RuntimeException("ForEach sub-flow not found: {$flowId}");
        }

        $context->log('info', "ForEach: Starting iteration over {$total} items using flow '{$subFlow->name}'");

        $runner = app(FlowRunner::class);
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($collection as $index => $item) {
            // Prepare payload for sub-flow
            $payload = [];

            if ($passContext) {
                $payload = array_merge($context->getPayload(), $context->getVariables());
            }

            $payload[$itemVariable] = $item;
            $payload[$indexVariable] = $index;
            $payload['foreach_total'] = $total;
            $payload['foreach_is_first'] = $index === 0;
            $payload['foreach_is_last'] = $index === $total - 1;

            // Execute sub-flow
            try {
                $result = $runner->run($subFlow, $payload);

                if ($result->status === 'completed') {
                    $successCount++;
                    if ($collectResults) {
                        $results[] = [
                            'index' => $index,
                            'status' => 'success',
                            'variables' => $result->context->getVariables(),
                        ];
                    }
                } else {
                    $failCount++;
                    if ($collectResults) {
                        $results[] = [
                            'index' => $index,
                            'status' => 'failed',
                            'error' => $result->error?->getMessage(),
                        ];
                    }

                    if ($stopOnError) {
                        $context->log('error', "ForEach: Stopped at index {$index} due to error");
                        break;
                    }
                }
            } catch (\Throwable $e) {
                $failCount++;
                if ($collectResults) {
                    $results[] = [
                        'index' => $index,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }

                if ($stopOnError) {
                    $context->log('error', "ForEach: Stopped at index {$index} due to exception: ".$e->getMessage());
                    break;
                }
            }

            // Delay between iterations
            if ($delayBetween > 0 && $index < $total - 1) {
                usleep($delayBetween * 1000);
            }
        }

        // Store results
        $context->set($resultsVariable, $results);
        $context->set('foreach_count', $total);
        $context->set('foreach_success', $successCount);
        $context->set('foreach_failed', $failCount);

        $context->log('info', "ForEach: Completed {$successCount}/{$total} iterations ({$failCount} failed)");

        return $context;
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
