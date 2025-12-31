<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Mail;

class SendMail extends Action
{
    public function name(): string
    {
        return 'Send Email';
    }

    public function description(): string
    {
        return 'Sends an email using Laravel Mail.';
    }

    public function icon(): string
    {
        return 'mail';
    }

    public function category(): string
    {
        return 'Communication';
    }

    public function fields(): array
    {
        return [
            Text::make('to')
                ->label('To')
                ->description('Recipient email address')
                ->supportsVariables()
                ->placeholder('{{ user.email }}')
                ->required(),

            Text::make('subject')
                ->label('Subject')
                ->supportsVariables()
                ->placeholder('Order #{{ order.id }} Confirmation')
                ->required(),

            Select::make('content_type')
                ->label('Content Type')
                ->options([
                    'markdown' => 'Markdown',
                    'html' => 'HTML',
                    'text' => 'Plain Text',
                    'mailable' => 'Mailable Class',
                ])
                ->default('markdown'),

            Textarea::make('body')
                ->label('Email Body')
                ->supportsVariables()
                ->description('Email content (supports variables)')
                ->visibleWhen('content_type', '!=', 'mailable'),

            Text::make('mailable_class')
                ->label('Mailable Class')
                ->placeholder('App\\Mail\\OrderConfirmation')
                ->visibleWhen('content_type', 'mailable'),

            Text::make('from_address')
                ->label('From Address')
                ->placeholder('Leave empty for default'),

            Text::make('from_name')
                ->label('From Name')
                ->placeholder('Leave empty for default'),

            Text::make('cc')
                ->label('CC')
                ->supportsVariables()
                ->placeholder('Comma-separated emails'),

            Text::make('bcc')
                ->label('BCC')
                ->supportsVariables()
                ->placeholder('Comma-separated emails'),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $to = $this->resolveValue($this->config('to'), $context);
        $subject = $this->resolveValue($this->config('subject'), $context);
        $contentType = $this->config('content_type', 'markdown');
        $body = $this->resolveValue($this->config('body', ''), $context);
        $fromAddress = $this->config('from_address');
        $fromName = $this->config('from_name');
        $cc = $this->resolveValue($this->config('cc', ''), $context);
        $bcc = $this->resolveValue($this->config('bcc', ''), $context);

        if ($contentType === 'mailable') {
            $mailableClass = $this->config('mailable_class');
            if (class_exists($mailableClass)) {
                $mailable = new $mailableClass($context->all());
                Mail::to($to)->send($mailable);
                $context->log('info', "Mailable sent to {$to}");

                return $context;
            }
            $context->log('error', "Mailable class not found: {$mailableClass}");

            return $context;
        }

        Mail::send([], [], function ($message) use ($to, $subject, $body, $contentType, $fromAddress, $fromName, $cc, $bcc) {
            $message->to($to)->subject($subject);

            if ($fromAddress) {
                $message->from($fromAddress, $fromName ?: null);
            }

            if ($cc) {
                $message->cc(array_map('trim', explode(',', $cc)));
            }

            if ($bcc) {
                $message->bcc(array_map('trim', explode(',', $bcc)));
            }

            match ($contentType) {
                'html' => $message->html($body),
                'text' => $message->text($body),
                default => $message->html($body),
            };
        });

        $context->log('info', "Email sent to {$to}");

        return $context;
    }
}
