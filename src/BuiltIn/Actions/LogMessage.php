<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\LogChannelSelect;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Log;

class LogMessage extends Action
{
    public function name(): string
    {
        return 'Log Message';
    }

    public function description(): string
    {
        return 'Logs a message to Laravel logs and/or flow context.';
    }

    public function icon(): string
    {
        return 'file-text';
    }

    public function category(): string
    {
        return 'Debugging';
    }

    public function fields(): array
    {
        return [
            Textarea::make('message')
                ->label('Message')
                ->supportsVariables()
                ->placeholder('Processing order {{ order.id }} for {{ user.name }}')
                ->required(),

            Select::make('level')
                ->label('Log Level')
                ->options([
                    'debug' => 'Debug',
                    'info' => 'Info',
                    'notice' => 'Notice',
                    'warning' => 'Warning',
                    'error' => 'Error',
                    'critical' => 'Critical',
                ])
                ->default('info'),

            LogChannelSelect::make('channel')
                ->label('Log Channel')
                ->description('Select a logging channel'),

            Toggle::make('log_to_laravel')
                ->label('Log to Laravel')
                ->description('Write to Laravel log files')
                ->default(true),

            Toggle::make('log_to_context')
                ->label('Log to Flow Context')
                ->description('Include in flow run logs')
                ->default(true),

            Toggle::make('include_context')
                ->label('Include Context Data')
                ->description('Add flow context as log context')
                ->default(false),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $message = $this->resolveValue($this->config('message'), $context);
        $level = $this->config('level', 'info');
        $channel = $this->config('channel');
        $logToLaravel = $this->config('log_to_laravel', true);
        $logToContext = $this->config('log_to_context', true);
        $includeContext = $this->config('include_context', false);

        $logContext = $includeContext ? ['flow_data' => $context->all()] : [];

        if ($logToLaravel) {
            $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();
            $logger->$level($message, $logContext);
        }

        if ($logToContext) {
            $context->log($message, $level);
        }

        return $context;
    }
}
