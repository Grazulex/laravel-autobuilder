<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Http;

class SendTeamsMessage extends Action
{
    public function name(): string
    {
        return 'Send Teams Message';
    }

    public function description(): string
    {
        return 'Sends a message to a Microsoft Teams channel via webhook.';
    }

    public function icon(): string
    {
        return 'messages-square';
    }

    public function category(): string
    {
        return 'Communication';
    }

    public function fields(): array
    {
        return [
            Text::make('webhook_url')
                ->label('Webhook URL')
                ->description('Teams incoming webhook or Workflows webhook URL')
                ->placeholder('https://example.webhook.office.com/...')
                ->required(),

            Select::make('format')
                ->label('Card Format')
                ->description('Message Card for incoming webhooks, Adaptive Card for Workflows webhooks')
                ->options([
                    'message_card' => 'Message Card',
                    'adaptive_card' => 'Adaptive Card',
                ])
                ->default('message_card'),

            Text::make('title')
                ->label('Title')
                ->description('Card title (optional)')
                ->supportsVariables()
                ->placeholder('New order'),

            Textarea::make('message')
                ->label('Message')
                ->supportsVariables()
                ->placeholder('New order received: {{ order.id }}')
                ->required(),

            Text::make('theme_color')
                ->label('Theme Color')
                ->description('Hex accent color, without # (Message Card only)')
                ->placeholder('0076D7')
                ->visibleWhen('format', 'message_card'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $webhookUrl = $this->config('webhook_url');
        $format = $this->config('format', 'message_card');
        $title = $this->resolveValue($this->config('title'), $context);
        $message = $this->resolveValue($this->config('message'), $context);

        $payload = $format === 'adaptive_card'
            ? $this->adaptiveCardPayload($title, $message)
            : $this->messageCardPayload($title, $message);

        $response = Http::post($webhookUrl, $payload);

        if ($response->successful()) {
            $context->log('info', 'Teams message sent successfully');
        } else {
            $context->log('error', 'Failed to send Teams message: '.$response->body());
        }

        return $context;
    }

    /**
     * Build a legacy Office 365 connector Message Card payload.
     *
     * @return array<string, mixed>
     */
    protected function messageCardPayload(mixed $title, mixed $message): array
    {
        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'summary' => $title ?: $message,
            'text' => $message,
        ];

        if ($title) {
            $payload['title'] = $title;
        }

        $themeColor = $this->config('theme_color');
        if ($themeColor) {
            $payload['themeColor'] = ltrim((string) $themeColor, '#');
        }

        return $payload;
    }

    /**
     * Build an Adaptive Card payload, as expected by Teams Workflows webhooks.
     *
     * @return array<string, mixed>
     */
    protected function adaptiveCardPayload(mixed $title, mixed $message): array
    {
        $body = [];

        if ($title) {
            $body[] = [
                'type' => 'TextBlock',
                'text' => $title,
                'weight' => 'Bolder',
                'size' => 'Medium',
                'wrap' => true,
            ];
        }

        $body[] = [
            'type' => 'TextBlock',
            'text' => $message,
            'wrap' => true,
        ];

        return [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => [
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'version' => '1.4',
                        'body' => $body,
                    ],
                ],
            ],
        ];
    }
}
