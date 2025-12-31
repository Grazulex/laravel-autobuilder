<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Notification;

class SendNotification extends Action
{
    public function name(): string
    {
        return 'Send Notification';
    }

    public function description(): string
    {
        return 'Sends a Laravel notification to users or notifiables.';
    }

    public function icon(): string
    {
        return 'bell';
    }

    public function category(): string
    {
        return 'Communication';
    }

    public function fields(): array
    {
        return [
            Text::make('notification_class')
                ->label('Notification Class')
                ->placeholder('App\\Notifications\\OrderConfirmation')
                ->description('Fully qualified notification class name')
                ->required(),

            Text::make('notifiable_field')
                ->label('Notifiable Field')
                ->description('Field containing the user/notifiable (e.g., user, order.customer)')
                ->supportsVariables()
                ->default('user'),

            Select::make('channels')
                ->label('Channels')
                ->options([
                    'mail' => 'Email',
                    'database' => 'Database',
                    'broadcast' => 'Broadcast',
                    'slack' => 'Slack',
                ])
                ->multiple()
                ->default(['mail']),

            Textarea::make('data')
                ->label('Notification Data (JSON)')
                ->description('Additional data to pass to the notification')
                ->supportsVariables()
                ->placeholder('{"order_id": "{{ order.id }}"}'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $notificationClass = $this->config('notification_class');
        $notifiableField = $this->config('notifiable_field', 'user');
        $channels = $this->config('channels', ['mail']);
        $dataJson = $this->resolveValue($this->config('data', '{}'), $context);

        $notifiable = $context->get($notifiableField);

        if (! $notifiable) {
            $context->log('warning', "SendNotification: Notifiable not found at '{$notifiableField}'");

            return $context;
        }

        if (! class_exists($notificationClass)) {
            $context->log('error', "SendNotification: Notification class '{$notificationClass}' not found");

            return $context;
        }

        $data = json_decode($dataJson, true) ?: [];
        $data['context'] = $context->all();

        $notification = new $notificationClass($data);

        if (method_exists($notification, 'via')) {
            // Let the notification decide channels
            Notification::send($notifiable, $notification);
        } else {
            // Use configured channels
            Notification::route('mail', $notifiable->email ?? null)
                ->notify($notification);
        }

        $context->log('info', "Notification sent via: ".implode(', ', $channels));

        return $context;
    }
}
