<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;

class DispatchJob extends Action
{
    public function name(): string
    {
        return 'Dispatch Job';
    }

    public function description(): string
    {
        return 'Dispatches a Laravel queue job.';
    }

    public function icon(): string
    {
        return 'layers';
    }

    public function category(): string
    {
        return 'Queue';
    }

    public function fields(): array
    {
        return [
            Text::make('job_class')
                ->label('Job Class')
                ->placeholder('App\\Jobs\\ProcessOrder')
                ->description('Fully qualified job class name')
                ->required(),

            Textarea::make('data')
                ->label('Job Data (JSON)')
                ->supportsVariables()
                ->description('Data to pass to the job constructor')
                ->placeholder('{"order_id": "{{ order.id }}"}'),

            Text::make('queue')
                ->label('Queue Name')
                ->placeholder('Leave empty for default queue'),

            Text::make('connection')
                ->label('Connection')
                ->placeholder('Leave empty for default connection'),

            Text::make('delay')
                ->label('Delay (seconds)')
                ->placeholder('0')
                ->description('Delay before job executes'),

            Toggle::make('dispatch_sync')
                ->label('Dispatch Synchronously')
                ->description('Run job immediately without queue')
                ->default(false),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $jobClass = $this->config('job_class');
        $dataJson = $this->resolveValue($this->config('data', '{}'), $context);
        $queue = $this->config('queue');
        $connection = $this->config('connection');
        $delay = (int) $this->config('delay', 0);
        $dispatchSync = $this->config('dispatch_sync', false);

        if (! class_exists($jobClass)) {
            $context->log('error', "DispatchJob: Job class '{$jobClass}' not found");

            return $context;
        }

        $data = json_decode($dataJson, true) ?: [];
        $data['flow_context'] = $context->all();

        $job = new $jobClass($data);

        if ($queue) {
            $job->onQueue($queue);
        }

        if ($connection) {
            $job->onConnection($connection);
        }

        if ($dispatchSync) {
            dispatch_sync($job);
            $context->log('info', "Job dispatched synchronously: {$jobClass}");
        } elseif ($delay > 0) {
            dispatch($job)->delay(now()->addSeconds($delay));
            $context->log('info', "Job dispatched with {$delay}s delay: {$jobClass}");
        } else {
            dispatch($job);
            $context->log('info', "Job dispatched: {$jobClass}");
        }

        return $context;
    }
}
