<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Http;

class SendDiscordMessage extends Action
{
    public function name(): string
    {
        return 'Send Discord Message';
    }

    public function description(): string
    {
        return 'Sends a message to a Discord channel via webhook.';
    }

    public function icon(): string
    {
        return 'message-circle';
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
                ->description('Discord channel webhook URL')
                ->placeholder('https://discord.com/api/webhooks/...')
                ->required(),

            Text::make('username')
                ->label('Bot Username')
                ->description('Override the webhook username (optional)')
                ->default('AutoBuilder')
                ->supportsVariables(),

            Text::make('avatar_url')
                ->label('Avatar URL')
                ->description('Override the webhook avatar (optional)')
                ->placeholder('https://example.com/avatar.png'),

            Textarea::make('message')
                ->label('Message')
                ->supportsVariables()
                ->placeholder('New order received: {{ order.id }}')
                ->required(),

            Toggle::make('tts')
                ->label('Text-to-Speech')
                ->description('Send as a text-to-speech message')
                ->default(false),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $webhookUrl = $this->config('webhook_url');
        $username = $this->resolveValue($this->config('username', 'AutoBuilder'), $context);
        $avatarUrl = $this->config('avatar_url');
        $message = $this->resolveValue($this->config('message'), $context);
        $tts = (bool) $this->config('tts', false);

        $payload = [
            'content' => $message,
        ];

        if ($username) {
            $payload['username'] = $username;
        }

        if ($avatarUrl) {
            $payload['avatar_url'] = $avatarUrl;
        }

        if ($tts) {
            $payload['tts'] = true;
        }

        $response = Http::post($webhookUrl, $payload);

        if ($response->successful()) {
            $context->log('info', 'Discord message sent successfully');
        } else {
            $context->log('error', 'Failed to send Discord message: '.$response->body());
        }

        return $context;
    }
}
