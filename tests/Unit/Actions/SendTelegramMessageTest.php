<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SendTelegramMessage;
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
        expect($this->registry->has(SendTelegramMessage::class))->toBeTrue();
    });

    it('has correct metadata', function () {
        $brick = $this->registry->resolve(SendTelegramMessage::class);

        expect($brick->name())->toBe('Send Telegram Message');
        expect($brick->category())->toBe('Communication');
        expect($brick->icon())->toBe('send');
        expect($brick->description())->toContain('Telegram');
    });

    it('has expected fields', function () {
        $brick = $this->registry->resolve(SendTelegramMessage::class);
        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $brick->fields());

        expect($fieldNames)->toBe(['bot_token', 'chat_id', 'message', 'parse_mode', 'disable_notification']);
    });

    it('offers telegram parse modes', function () {
        $brick = $this->registry->resolve(SendTelegramMessage::class);
        $parseModeField = array_values(array_filter(
            $brick->fields(),
            fn ($f): bool => $f->toArray()['name'] === 'parse_mode'
        ))[0]->toArray();

        expect(array_column($parseModeField['options'], 'value'))->toBe(['', 'MarkdownV2', 'HTML']);
    });
});

describe('handle', function () {
    it('calls the sendMessage endpoint with resolved variables', function () {
        Http::fake(['*' => Http::response(['ok' => true, 'result' => []], 200)]);

        $brick = $this->registry->resolve(SendTelegramMessage::class, [
            'bot_token' => '123456:ABC',
            'chat_id' => '{{ chat }}',
            'message' => 'Order {{ order_id }} received',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('chat', '-100200300');
        $context->set('order_id', 42);

        $result = $brick->handle($context);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bot123456:ABC/sendMessage'
            && $request->method() === 'POST'
            && $request['chat_id'] === '-100200300'
            && $request['text'] === 'Order 42 received'
            && ! isset($request['parse_mode'])
            && ! isset($request['disable_notification']));

        $infoLogs = array_values(array_filter($result->logs, fn ($log): bool => $log['level'] === 'info'));
        expect($infoLogs[0]['message'])->toBe('Telegram message sent successfully');
    });

    it('sends parse mode and silent flag when configured', function () {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $brick = $this->registry->resolve(SendTelegramMessage::class, [
            'bot_token' => '123456:ABC',
            'chat_id' => '@my_channel',
            'message' => '<b>Hello</b>',
            'parse_mode' => 'HTML',
            'disable_notification' => true,
        ]);

        $brick->handle(new FlowContext('flow-1'));

        Http::assertSent(fn (Request $request): bool => $request['chat_id'] === '@my_channel'
            && $request['parse_mode'] === 'HTML'
            && $request['disable_notification'] === true);
    });

    it('logs an error without exposing the bot token', function () {
        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)]);

        $brick = $this->registry->resolve(SendTelegramMessage::class, [
            'bot_token' => '123456:SECRET',
            'chat_id' => '1',
            'message' => 'Hello',
        ]);

        $result = $brick->handle(new FlowContext('flow-1'));

        $errorLogs = array_values(array_filter($result->logs, fn ($log): bool => $log['level'] === 'error'));
        expect($errorLogs)->not->toBeEmpty();
        expect($errorLogs[0]['message'])->toContain('Failed to send Telegram message');
        expect($errorLogs[0]['message'])->toContain('chat not found');
        expect($errorLogs[0]['message'])->not->toContain('SECRET');
    });
});
