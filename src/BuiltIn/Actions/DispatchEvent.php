<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Flow\FlowContext;

class DispatchEvent extends Action
{
    public function name(): string
    {
        return 'Dispatch Event';
    }

    public function description(): string
    {
        return 'Dispatches a Laravel event.';
    }

    public function icon(): string
    {
        return 'zap';
    }

    public function category(): string
    {
        return 'Events';
    }

    public function fields(): array
    {
        return [
            Text::make('event_class')
                ->label('Event Class')
                ->placeholder('App\\Events\\OrderShipped')
                ->description('Fully qualified event class name')
                ->required(),

            Textarea::make('data')
                ->label('Event Data (JSON)')
                ->supportsVariables()
                ->description('Data to pass to the event constructor')
                ->placeholder('{"order_id": "{{ order.id }}"}'),

            Text::make('store_result')
                ->label('Store Listener Results As')
                ->description('Variable name to store listener return values')
                ->placeholder('event_results'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $eventClass = $this->config('event_class');
        $dataJson = $this->resolveValue($this->config('data', '{}'), $context);
        $storeResult = $this->config('store_result');

        if (! class_exists($eventClass)) {
            $context->log('error', "DispatchEvent: Event class '{$eventClass}' not found");

            return $context;
        }

        $data = json_decode($dataJson, true) ?: [];
        $data['flow_context'] = $context->all();

        $event = new $eventClass($data);

        $results = event($event);

        if ($storeResult) {
            $context->set($storeResult, $results);
        }

        $context->log('info', "Event dispatched: {$eventClass}");

        return $context;
    }
}
