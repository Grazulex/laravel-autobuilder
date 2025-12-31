<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;

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
                ->prefix('/autobuilder/webhook/')
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
        return url('/autobuilder/webhook/' . $this->config('path'));
    }

    public function samplePayload(): array
    {
        return [
            'method' => 'POST',
            'path' => $this->config('path', 'webhook'),
            'query' => [],
            'body' => ['example' => 'data'],
            'headers' => [
                'content-type' => ['application/json'],
                'user-agent' => ['WebhookClient/1.0'],
            ],
        ];
    }
}
