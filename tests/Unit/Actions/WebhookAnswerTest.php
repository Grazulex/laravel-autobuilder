<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\WebhookAnswer;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class);

        expect($brick->name())->toBe('Webhook Answer');
        expect($brick->type())->toBe('action');
        expect($brick->category())->toBe('External');
        expect($brick->icon())->toBe('reply');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class);

        expect($brick->description())->toContain('webhook');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('status_code');
        expect($fieldNames)->toContain('content_type');
        expect($fieldNames)->toContain('response_body');
        expect($fieldNames)->toContain('response_headers');
    });

    it('has 4 fields total', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class);
        expect($brick->fields())->toHaveCount(4);
    });
});

// =============================================================================
// Handle Tests
// =============================================================================

describe('handle', function () {
    it('sets webhook response in context', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class, [
            'status_code' => 201,
            'content_type' => 'application/json',
            'response_body' => '{"created":true}',
            'response_headers' => [],
        ]);

        $context = new FlowContext('test-flow', []);
        $result = $brick->handle($context);

        expect($result->hasWebhookResponse())->toBeTrue();

        $response = $result->getWebhookResponse();
        expect($response['status_code'])->toBe(201);
        expect($response['content_type'])->toBe('application/json');
        expect($response['body'])->toBe('{"created":true}');
    });

    it('uses default values when not configured', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class, []);

        $context = new FlowContext('test-flow', []);
        $result = $brick->handle($context);

        expect($result->hasWebhookResponse())->toBeTrue();

        $response = $result->getWebhookResponse();
        expect($response['status_code'])->toBe(200);
        expect($response['content_type'])->toBe('application/json');
    });

    it('sets custom headers', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class, [
            'status_code' => 200,
            'content_type' => 'application/json',
            'response_body' => '{}',
            'response_headers' => ['X-Custom' => 'test-value'],
        ]);

        $context = new FlowContext('test-flow', []);
        $result = $brick->handle($context);

        $response = $result->getWebhookResponse();
        expect($response['headers'])->toBe(['X-Custom' => 'test-value']);
    });

    it('supports variable resolution in response body', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class, [
            'status_code' => 200,
            'content_type' => 'text/plain',
            'response_body' => 'Hello {{ user_name }}',
            'response_headers' => [],
        ]);

        $context = new FlowContext('test-flow', []);
        $context->set('user_name', 'World');
        $result = $brick->handle($context);

        $response = $result->getWebhookResponse();
        expect($response['body'])->toBe('Hello World');
    });

    it('logs webhook response info', function () {
        $brick = $this->registry->resolve(WebhookAnswer::class, [
            'status_code' => 404,
            'content_type' => 'text/plain',
            'response_body' => 'Not Found',
            'response_headers' => [],
        ]);

        $context = new FlowContext('test-flow', []);
        $result = $brick->handle($context);

        $lastLog = end($result->logs);
        expect($lastLog['message'])->toContain('HTTP 404');
    });
});
