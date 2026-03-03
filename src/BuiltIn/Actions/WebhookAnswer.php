<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Code;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Flow\FlowContext;

class WebhookAnswer extends Action
{
    public function name(): string
    {
        return 'Webhook Answer';
    }

    public function description(): string
    {
        return 'Sends a custom HTTP response to the incoming webhook request.';
    }

    public function icon(): string
    {
        return 'reply';
    }

    public function category(): string
    {
        return 'External';
    }

    public function fields(): array
    {
        return [
            Number::make('status_code')
                ->label('Status Code')
                ->description('HTTP status code for the response')
                ->default(200)
                ->required(),

            Select::make('content_type')
                ->label('Content Type')
                ->options([
                    'application/json' => 'JSON',
                    'text/plain' => 'Plain Text',
                    'text/html' => 'HTML',
                    'application/xml' => 'XML',
                ])
                ->default('application/json'),

            Code::make('response_body')
                ->label('Response Body')
                ->description('The response body to send back. Supports {{ variables }}.')
                ->supportsVariables()
                ->default('{"status": "ok"}'),

            KeyValue::make('response_headers')
                ->label('Response Headers')
                ->description('Additional HTTP response headers'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $statusCode = (int) $this->config('status_code', 200);
        $contentType = $this->config('content_type', 'application/json');
        $body = $this->resolveValue($this->config('response_body', ''), $context);
        $headers = $this->config('response_headers', []);

        $context->setWebhookResponse($statusCode, $contentType, $body, $headers);

        $context->info("Webhook response set: HTTP {$statusCode} ({$contentType})");

        return $context;
    }
}
