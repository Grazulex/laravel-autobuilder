<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Http;

class SendTelegramMessage extends Action
{
    public function name(): string
    {
        return 'Send Telegram Message';
    }

    public function description(): string
    {
        return 'Sends a message to a Telegram chat via the Bot API.';
    }

    public function icon(): string
    {
        return 'send';
    }

    public function category(): string
    {
        return 'Communication';
    }

    public function fields(): array
    {
        return [
            Text::make('bot_token')
                ->label('Bot Token')
                ->description('Telegram bot token issued by @BotFather')
                ->placeholder('123456789:ABC-DEF...')
                ->required(),

            Text::make('chat_id')
                ->label('Chat ID')
                ->description('Target chat ID or @channelusername')
                ->supportsVariables()
                ->placeholder('-1001234567890')
                ->required(),

            Textarea::make('message')
                ->label('Message')
                ->supportsVariables()
                ->placeholder('New order received: {{ order.id }}')
                ->required(),

            Select::make('parse_mode')
                ->label('Parse Mode')
                ->description('How Telegram formats the message text')
                ->options([
                    '' => 'Plain text',
                    'MarkdownV2' => 'MarkdownV2',
                    'HTML' => 'HTML',
                ])
                ->default(''),

            Toggle::make('disable_notification')
                ->label('Silent')
                ->description('Send the message without a notification sound')
                ->default(false),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $botToken = (string) $this->config('bot_token');
        $chatId = $this->resolveValue($this->config('chat_id'), $context);
        $message = $this->resolveValue($this->config('message'), $context);
        $parseMode = $this->config('parse_mode');
        $silent = (bool) $this->config('disable_notification', false);

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if ($parseMode) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($silent) {
            $payload['disable_notification'] = true;
        }

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

        if ($response->successful()) {
            $context->log('info', 'Telegram message sent successfully');
        } else {
            $context->log('error', 'Failed to send Telegram message: '.$response->body());
        }

        return $context;
    }
}
