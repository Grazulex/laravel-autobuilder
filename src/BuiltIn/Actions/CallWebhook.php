<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Http;

class CallWebhook extends Action
{
    public function name(): string
    {
        return 'Call Webhook';
    }

    public function description(): string
    {
        return 'Makes an HTTP request to an external webhook or API.';
    }

    public function icon(): string
    {
        return 'globe';
    }

    public function category(): string
    {
        return 'Integration';
    }

    public function fields(): array
    {
        return [
            Text::make('url')
                ->label('URL')
                ->supportsVariables()
                ->placeholder('https://api.example.com/webhook')
                ->required(),

            Select::make('method')
                ->label('HTTP Method')
                ->options([
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                ])
                ->default('POST'),

            KeyValue::make('headers')
                ->label('Headers')
                ->supportsVariables()
                ->description('HTTP headers to include'),

            Textarea::make('body')
                ->label('Request Body (JSON)')
                ->supportsVariables()
                ->placeholder('{"order": {{ order | json }}}')
                ->visibleWhen('method', '!=', 'GET'),

            Select::make('body_format')
                ->label('Body Format')
                ->options([
                    'json' => 'JSON',
                    'form' => 'Form Data',
                    'multipart' => 'Multipart',
                ])
                ->default('json')
                ->visibleWhen('method', '!=', 'GET'),

            Number::make('timeout')
                ->label('Timeout (seconds)')
                ->default(30)
                ->min(1)
                ->max(300),

            Text::make('store_response')
                ->label('Store Response As')
                ->description('Variable name to store the response')
                ->default('webhook_response'),

            Select::make('retry_times')
                ->label('Retry on Failure')
                ->options([
                    '0' => 'No retry',
                    '1' => '1 retry',
                    '2' => '2 retries',
                    '3' => '3 retries',
                ])
                ->default('0'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $url = $this->resolveValue($this->config('url'), $context);
        $method = $this->config('method', 'POST');
        $headers = $this->config('headers', []);
        $bodyJson = $this->resolveValue($this->config('body', '{}'), $context);

        // Handle JSON string from frontend
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?? [];
        }
        $bodyFormat = $this->config('body_format', 'json');
        $timeout = (int) $this->config('timeout', 30);
        $storeResponse = $this->config('store_response', 'webhook_response');
        $retryTimes = (int) $this->config('retry_times', 0);

        // Resolve headers
        $resolvedHeaders = [];
        foreach ($headers as $key => $value) {
            $resolvedHeaders[$key] = $this->resolveValue($value, $context);
        }

        $http = Http::withHeaders($resolvedHeaders)
            ->timeout($timeout);

        if ($retryTimes > 0) {
            $http = $http->retry($retryTimes, 100);
        }

        $body = json_decode($bodyJson, true) ?: [];

        $response = match ($method) {
            'GET' => $http->get($url),
            'POST' => $this->sendWithBody($http, 'post', $url, $body, $bodyFormat),
            'PUT' => $this->sendWithBody($http, 'put', $url, $body, $bodyFormat),
            'PATCH' => $this->sendWithBody($http, 'patch', $url, $body, $bodyFormat),
            'DELETE' => $http->delete($url, $body),
            default => $http->post($url, $body),
        };

        $responseData = [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->json() ?? $response->body(),
            'headers' => $response->headers(),
        ];

        $context->set($storeResponse, $responseData);

        if ($response->successful()) {
            $context->log('info', "Webhook called successfully: {$method} {$url} (Status: {$response->status()})");
        } else {
            $context->log('warning', "Webhook returned error: {$method} {$url} (Status: {$response->status()})");
        }

        return $context;
    }

    private function sendWithBody($http, string $method, string $url, array $body, string $format)
    {
        return match ($format) {
            'form' => $http->asForm()->$method($url, $body),
            'multipart' => $http->asMultipart()->$method($url, $body),
            default => $http->$method($url, $body),
        };
    }
}
