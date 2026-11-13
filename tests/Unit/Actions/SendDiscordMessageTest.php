<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SendDiscordMessage;
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
        expect($this->registry->has(SendDiscordMessage::class))->toBeTrue();
    });

    it('has correct metadata', function () {
        $brick = $this->registry->resolve(SendDiscordMessage::class);

        expect($brick->name())->toBe('Send Discord Message');
        expect($brick->category())->toBe('Communication');
        expect($brick->icon())->toBe('message-circle');
        expect($brick->description())->toContain('Discord');
    });

    it('has expected fields', function () {
        $brick = $this->registry->resolve(SendDiscordMessage::class);
        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $brick->fields());

        expect($fieldNames)->toBe(['webhook_url', 'username', 'avatar_url', 'message', 'tts']);
    });
});

describe('handle', function () {
    it('posts the message to the webhook with resolved variables', function () {
        Http::fake(['*' => Http::response(null, 204)]);

        $brick = $this->registry->resolve(SendDiscordMessage::class, [
            'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
            'message' => 'Order {{ order_id }} received',
            'username' => 'Shop Bot',
            'avatar_url' => 'https://example.com/avatar.png',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('order_id', 42);

        $result = $brick->handle($context);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.com/api/webhooks/1/abc'
            && $request->method() === 'POST'
            && $request['content'] === 'Order 42 received'
            && $request['username'] === 'Shop Bot'
            && $request['avatar_url'] === 'https://example.com/avatar.png'
            && ! isset($request['tts']));

        $infoLogs = array_filter($result->logs, fn ($log): bool => $log['level'] === 'info');
        expect(array_values($infoLogs)[0]['message'])->toBe('Discord message sent successfully');
    });

    it('sends tts flag when enabled', function () {
        Http::fake(['*' => Http::response(null, 204)]);

        $brick = $this->registry->resolve(SendDiscordMessage::class, [
            'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
            'message' => 'Hello',
            'tts' => true,
        ]);

        $brick->handle(new FlowContext('flow-1'));

        Http::assertSent(fn (Request $request): bool => $request['tts'] === true);
    });

    it('uses default username when none is configured', function () {
        Http::fake(['*' => Http::response(null, 204)]);

        $brick = $this->registry->resolve(SendDiscordMessage::class, [
            'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
            'message' => 'Hello',
        ]);

        $brick->handle(new FlowContext('flow-1'));

        Http::assertSent(fn (Request $request): bool => $request['username'] === 'AutoBuilder'
            && ! isset($request['avatar_url']));
    });

    it('logs an error when the webhook fails', function () {
        Http::fake(['*' => Http::response(['message' => 'Unknown Webhook'], 404)]);

        $brick = $this->registry->resolve(SendDiscordMessage::class, [
            'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
            'message' => 'Hello',
        ]);

        $result = $brick->handle(new FlowContext('flow-1'));

        $errorLogs = array_values(array_filter($result->logs, fn ($log): bool => $log['level'] === 'error'));
        expect($errorLogs)->not->toBeEmpty();
        expect($errorLogs[0]['message'])->toContain('Failed to send Discord message');
        expect($errorLogs[0]['message'])->toContain('Unknown Webhook');
    });
});
