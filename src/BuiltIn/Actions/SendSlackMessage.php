<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Fields\Toggle;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Http;

class SendSlackMessage extends Action
{
    public function name(): string
    {
        return 'Send Slack Message';
    }

    public function description(): string
    {
        return 'Sends a message to a Slack channel via webhook.';
    }

    public function icon(): string
    {
        return 'message-square';
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
                ->description('Slack incoming webhook URL')
                ->placeholder('https://hooks.slack.com/services/...')
                ->required(),

            Text::make('channel')
                ->label('Channel')
                ->description('Override default channel (optional)')
                ->placeholder('#general'),

            Text::make('username')
                ->label('Bot Username')
                ->default('AutoBuilder')
                ->supportsVariables(),

            Text::make('icon_emoji')
                ->label('Icon Emoji')
                ->default(':robot_face:')
                ->placeholder(':rocket:'),

            Textarea::make('message')
                ->label('Message')
                ->supportsVariables()
                ->placeholder('New order received: {{ order.id }}')
                ->required(),

            Toggle::make('use_blocks')
                ->label('Use Block Kit')
                ->description('Send as Slack Block Kit format')
                ->default(false),

            Textarea::make('blocks')
                ->label('Blocks JSON')
                ->description('Slack Block Kit JSON (when enabled)')
                ->supportsVariables()
                ->visibleWhen('use_blocks', true),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $webhookUrl = $this->config('webhook_url');
        $channel = $this->config('channel');
        $username = $this->resolveValue($this->config('username', 'AutoBuilder'), $context);
        $iconEmoji = $this->config('icon_emoji', ':robot_face:');
        $message = $this->resolveValue($this->config('message'), $context);
        $useBlocks = $this->config('use_blocks', false);

        $payload = [
            'text' => $message,
            'username' => $username,
            'icon_emoji' => $iconEmoji,
        ];

        if ($channel) {
            $payload['channel'] = $channel;
        }

        if ($useBlocks) {
            $blocksJson = $this->resolveValue($this->config('blocks', '[]'), $context);
            $blocks = json_decode($blocksJson, true);
            if ($blocks) {
                $payload['blocks'] = $blocks;
            }
        }

        $response = Http::post($webhookUrl, $payload);

        if ($response->successful()) {
            $context->log('info', 'Slack message sent successfully');
        } else {
            $context->log('error', 'Failed to send Slack message: '.$response->body());
        }

        return $context;
    }
}
