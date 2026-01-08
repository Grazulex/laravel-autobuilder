<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\CallWebhook;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(CallWebhook::class);

        expect($brick->name())->toBe('Call Webhook');
        expect($brick->category())->toBe('Integration');
        expect($brick->icon())->toBe('globe');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('url');
        expect($fieldNames)->toContain('method');
        expect($fieldNames)->toContain('headers');
        expect($fieldNames)->toContain('body');
        expect($fieldNames)->toContain('body_format');
        expect($fieldNames)->toContain('timeout');
        expect($fieldNames)->toContain('store_response');
        expect($fieldNames)->toContain('retry_times');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(CallWebhook::class);

        expect($brick->description())->toContain('HTTP');
    });

    it('has 8 fields total', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(8);
    });
});

// =============================================================================
// HTTP Method Tests
// =============================================================================

describe('http methods', function () {
    it('makes GET request', function () {
        Http::fake([
            'https://api.example.com/data' => Http::response(['status' => 'ok'], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        Http::assertSent(fn ($request) => $request->method() === 'GET');

        $response = $result->get('response');
        expect($response['status'])->toBe(200);
        expect($response['successful'])->toBeTrue();
    });

    it('makes POST request', function () {
        Http::fake([
            'https://api.example.com/create' => Http::response(['id' => 123], 201),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/create',
            'method' => 'POST',
            'body' => '{"name": "Test"}',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        Http::assertSent(fn ($request) => $request->method() === 'POST');

        $response = $result->get('response');
        expect($response['status'])->toBe(201);
        expect($response['body']['id'])->toBe(123);
    });

    it('makes PUT request', function () {
        Http::fake([
            'https://api.example.com/update' => Http::response(['updated' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/update',
            'method' => 'PUT',
            'body' => '{"name": "Updated"}',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        Http::assertSent(fn ($request) => $request->method() === 'PUT');
    });

    it('makes PATCH request', function () {
        Http::fake([
            'https://api.example.com/patch' => Http::response(['patched' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/patch',
            'method' => 'PATCH',
            'body' => '{"field": "value"}',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        Http::assertSent(fn ($request) => $request->method() === 'PATCH');
    });

    it('makes DELETE request', function () {
        Http::fake([
            'https://api.example.com/delete' => Http::response(null, 204),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/delete',
            'method' => 'DELETE',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        Http::assertSent(fn ($request) => $request->method() === 'DELETE');

        $response = $result->get('response');
        expect($response['status'])->toBe(204);
    });
});

// =============================================================================
// Variable Resolution Tests
// =============================================================================

describe('variable resolution', function () {
    it('resolves variables in url', function () {
        Http::fake([
            'https://api.example.com/users/42' => Http::response(['id' => 42], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/users/{{ user_id }}',
            'method' => 'GET',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user_id', 42);

        $result = $brick->handle($context);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/users/42'));
    });

    it('resolves variables in body', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/create',
            'method' => 'POST',
            'body' => '{"name": "{{ user_name }}", "email": "{{ user_email }}"}',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user_name', 'John Doe');
        $context->set('user_email', 'john@example.com');

        $brick->handle($context);

        Http::assertSent(function ($request) {
            $body = $request->body();

            return str_contains($body, 'John Doe') && str_contains($body, 'john@example.com');
        });
    });

    it('resolves variables in headers', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer {{ api_token }}',
                'X-Custom-Header' => '{{ custom_value }}',
            ],
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('api_token', 'secret-token-123');
        $context->set('custom_value', 'custom-value');

        $brick->handle($context);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-token-123')
                && $request->hasHeader('X-Custom-Header', 'custom-value');
        });
    });
});

// =============================================================================
// Headers Tests
// =============================================================================

describe('headers', function () {
    it('sends custom headers', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-API-Key' => 'my-api-key',
            ],
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $brick->handle($context);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-API-Key', 'my-api-key');
        });
    });

    it('parses JSON string headers', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'headers' => json_encode([
                'X-Custom' => 'from-json',
            ]),
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $brick->handle($context);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Custom', 'from-json');
        });
    });
});

// =============================================================================
// Body Format Tests
// =============================================================================

describe('body format', function () {
    it('sends json body format', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/create',
            'method' => 'POST',
            'body' => '{"key": "value"}',
            'body_format' => 'json',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $brick->handle($context);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json');
        });
    });

    it('sends form body format', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/form',
            'method' => 'POST',
            'body' => '{"field1": "value1", "field2": "value2"}',
            'body_format' => 'form',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $brick->handle($context);

        Http::assertSent(function ($request) {
            $contentType = $request->header('Content-Type')[0] ?? '';

            return str_contains($contentType, 'application/x-www-form-urlencoded');
        });
    });
});

// =============================================================================
// Response Storage Tests
// =============================================================================

describe('response storage', function () {
    it('stores response in context with custom key', function () {
        Http::fake([
            '*' => Http::response(['data' => 'test'], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'store_response' => 'api_result',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('api_result'))->not->toBeNull();
        expect($result->get('api_result')['body']['data'])->toBe('test');
    });

    it('stores response status code', function () {
        Http::fake([
            '*' => Http::response(['data' => 'test'], 201),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'POST',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('response')['status'])->toBe(201);
    });

    it('stores successful flag', function () {
        Http::fake([
            '*' => Http::response(['data' => 'test'], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('response')['successful'])->toBeTrue();
    });

    it('stores unsuccessful flag for error responses', function () {
        Http::fake([
            '*' => Http::response(['error' => 'not found'], 404),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('response')['successful'])->toBeFalse();
        expect($result->get('response')['status'])->toBe(404);
    });

    it('stores response headers', function () {
        Http::fake([
            '*' => Http::response(['data' => 'test'], 200, ['X-Custom-Header' => 'custom-value']),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('response')['headers'])->toBeArray();
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs successful request', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $firstLog = array_values($infoLogs)[0]['message'];
        expect($firstLog)->toContain('successfully');
        expect($firstLog)->toContain('GET');
        expect($firstLog)->toContain('200');
    });

    it('logs warning for error responses', function () {
        Http::fake([
            '*' => Http::response(['error' => 'server error'], 500),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'POST',
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();

        $firstWarning = array_values($warningLogs)[0]['message'];
        expect($firstWarning)->toContain('error');
        expect($firstWarning)->toContain('500');
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default method of POST', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $methodField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'method');
        $defaultValue = array_values($methodField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('POST');
    });

    it('uses default body_format of json', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $bodyFormatField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'body_format');
        $defaultValue = array_values($bodyFormatField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('json');
    });

    it('uses default timeout of 30', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $timeoutField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'timeout');
        $defaultValue = array_values($timeoutField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe(30);
    });

    it('uses default store_response of webhook_response', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $storeResponseField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'store_response');
        $defaultValue = array_values($storeResponseField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('webhook_response');
    });

    it('uses default retry_times of 0', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $retryField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'retry_times');
        $defaultValue = array_values($retryField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('0');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('method field has correct options', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $methodField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'method');
        $options = array_values($methodField)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('GET');
        expect(array_column($options, 'value'))->toContain('POST');
        expect(array_column($options, 'value'))->toContain('PUT');
        expect(array_column($options, 'value'))->toContain('PATCH');
        expect(array_column($options, 'value'))->toContain('DELETE');
    });

    it('body_format field has correct options', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $bodyFormatField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'body_format');
        $options = array_values($bodyFormatField)[0]->toArray()['options'] ?? [];

        expect(array_column($options, 'value'))->toContain('json');
        expect(array_column($options, 'value'))->toContain('form');
        expect(array_column($options, 'value'))->toContain('multipart');
    });

    it('retry_times field has correct options', function () {
        $brick = $this->registry->resolve(CallWebhook::class);
        $fields = $brick->fields();

        $retryField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'retry_times');
        $options = array_values($retryField)[0]->toArray()['options'] ?? [];

        // Options are now in {value, label} format
        $optionLabels = array_column($options, 'label');
        expect($optionLabels)->toContain('No retry');
        expect($optionLabels)->toContain('1 retry');
        expect($optionLabels)->toContain('2 retries');
        expect($optionLabels)->toContain('3 retries');
    });
});

// =============================================================================
// Timeout Tests
// =============================================================================

describe('timeout', function () {
    it('uses configured timeout', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $brick = $this->registry->resolve(CallWebhook::class, [
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'timeout' => 60,
            'store_response' => 'response',
        ]);

        $context = new FlowContext('flow-1');
        $brick->handle($context);

        // Timeout is applied internally, we can verify the request was made
        Http::assertSentCount(1);
    });
});
