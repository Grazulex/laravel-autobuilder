<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Support\WebhookPathNormalizer;

class OnWebhookReceived extends Trigger
{
    public function name(): string
    {
        return 'Webhook Received';
    }

    public function description(): string
    {
        return 'Triggers when a webhook is received at a custom URL.';
    }

    public function icon(): string
    {
        return 'webhook';
    }

    public function category(): string
    {
        return 'External';
    }

    public function fields(): array
    {
        return [
            Text::make('path')
                ->label('Webhook Path')
                ->prefix('/'.config('autobuilder.routes.prefix', 'autobuilder').'/webhook/')
                ->placeholder('my-webhook')
                ->description('Unique path for this webhook')
                ->required(),

            Select::make('method')
                ->label('HTTP Method')
                ->options([
                    'POST' => 'POST',
                    'GET' => 'GET',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                    'ANY' => 'Any Method',
                ])
                ->default('POST'),

            Text::make('secret')
                ->label('Secret (optional)')
                ->description('If set, validates X-Webhook-Secret header'),
        ];
    }

    public function register(): void
    {
        // Handled by WebhookController
    }

    public function getWebhookUrl(): string
    {
        $prefix = config('autobuilder.routes.prefix', 'autobuilder');
        $path = WebhookPathNormalizer::normalize($this->config('path')) ?? $this->config('path');

        return url("/{$prefix}/webhook/{$path}");
    }

    public function samplePayload(): array
    {
        $path = $this->config('path', 'webhook');

        return [
            'webhook' => [
                'method' => 'POST',
                'path' => $path,
                'query' => [],
                'payload' => ['example' => 'data'],
                'headers' => [
                    'content-type' => ['application/json'],
                    'user-agent' => ['WebhookClient/1.0'],
                ],
                'ip' => '127.0.0.1',
                'content_type' => 'application/json',
                'user_agent' => 'WebhookClient/1.0',
            ],
            'method' => 'POST',
            'path' => $path,
            'query' => [],
            'body' => ['example' => 'data'],
            'headers' => [
                'content-type' => ['application/json'],
                'user-agent' => ['WebhookClient/1.0'],
            ],
        ];
    }
}
