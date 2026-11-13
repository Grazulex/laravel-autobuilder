<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SendTeamsMessage;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

describe('metadata', function () {
    it('is discovered as a built-in action', function () {
        expect($this->registry->has(SendTeamsMessage::class))->toBeTrue();
    });

    it('has correct metadata', function () {
        $brick = $this->registry->resolve(SendTeamsMessage::class);

        expect($brick->name())->toBe('Send Teams Message');
        expect($brick->category())->toBe('Communication');
        expect($brick->icon())->toBe('messages-square');
        expect($brick->description())->toContain('Teams');
    });

    it('has expected fields', function () {
        $brick = $this->registry->resolve(SendTeamsMessage::class);
        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $brick->fields());

        expect($fieldNames)->toBe(['webhook_url', 'format', 'title', 'message', 'theme_color']);
    });

    it('offers message card and adaptive card formats', function () {
        $brick = $this->registry->resolve(SendTeamsMessage::class);
        $formatField = array_values(array_filter(
            $brick->fields(),
            fn ($f): bool => $f->toArray()['name'] === 'format'
        ))[0]->toArray();

        expect(array_column($formatField['options'], 'value'))->toBe(['message_card', 'adaptive_card']);
        expect($formatField['default'])->toBe('message_card');
    });
});

describe('handle', function () {
    it('sends a message card by default', function () {
        Http::fake(['*' => Http::response('1', 200)]);

        $brick = $this->registry->resolve(SendTeamsMessage::class, [
            'webhook_url' => 'https://example.webhook.office.com/webhookb2/abc',
            'title' => 'Order {{ order_id }}',
            'message' => 'Total: {{ total }}',
            'theme_color' => '#0076D7',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('order_id', 42);
        $context->set('total', '99.90');

        $result = $brick->handle($context);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.webhook.office.com/webhookb2/abc'
            && $request['@type'] === 'MessageCard'
            && $request['title'] === 'Order 42'
            && $request['text'] === 'Total: 99.90'
            && $request['summary'] === 'Order 42'
            && $request['themeColor'] === '0076D7');

        $infoLogs = array_values(array_filter($result->logs, fn ($log): bool => $log['level'] === 'info'));
        expect($infoLogs[0]['message'])->toBe('Teams message sent successfully');
    });

    it('omits optional message card properties when empty', function () {
        Http::fake(['*' => Http::response('1', 200)]);

        $brick = $this->registry->resolve(SendTeamsMessage::class, [
            'webhook_url' => 'https://example.webhook.office.com/webhookb2/abc',
            'message' => 'Hello',
        ]);

        $brick->handle(new FlowContext('flow-1'));

        Http::assertSent(fn (Request $request): bool => $request['summary'] === 'Hello'
            && ! isset($request['title'])
            && ! isset($request['themeColor']));
    });

    it('sends an adaptive card when configured', function () {
        Http::fake(['*' => Http::response(null, 202)]);

        $brick = $this->registry->resolve(SendTeamsMessage::class, [
            'webhook_url' => 'https://prod.example.logic.azure.com/workflows/abc',
            'format' => 'adaptive_card',
            'title' => 'Alert',
            'message' => 'Hello {{ name }}',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('name', 'Jane');

        $brick->handle($context);

        Http::assertSent(function (Request $request): bool {
            $attachment = $request['attachments'][0];
            $body = $attachment['content']['body'];

            return $request['type'] === 'message'
                && $attachment['contentType'] === 'application/vnd.microsoft.card.adaptive'
                && $attachment['content']['type'] === 'AdaptiveCard'
                && count($body) === 2
                && $body[0]['text'] === 'Alert'
                && $body[1]['text'] === 'Hello Jane';
        });
    });

    it('logs an error when the webhook fails', function () {
        Http::fake(['*' => Http::response('Bad payload', 400)]);

        $brick = $this->registry->resolve(SendTeamsMessage::class, [
            'webhook_url' => 'https://example.webhook.office.com/webhookb2/abc',
            'message' => 'Hello',
        ]);

        $result = $brick->handle(new FlowContext('flow-1'));

        $errorLogs = array_values(array_filter($result->logs, fn ($log): bool => $log['level'] === 'error'));
        expect($errorLogs)->not->toBeEmpty();
        expect($errorLogs[0]['message'])->toContain('Failed to send Teams message');
        expect($errorLogs[0]['message'])->toContain('Bad payload');
    });
});
