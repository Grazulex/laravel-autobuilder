<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnWebhookReceived;
use Grazulex\AutoBuilder\Models\Flow;

/**
 * Helper to create a webhook trigger node.
 */
function webhookTriggerNode(string $path, ?string $secret = null, string $method = 'POST'): array
{
    $config = [
        'path' => $path,
        'method' => $method,
    ];

    if ($secret !== null) {
        $config['secret'] = $secret;
    }

    return [
        'id' => 'trigger-1',
        'type' => 'trigger',
        'position' => ['x' => 100, 'y' => 100],
        'data' => [
            'brick' => OnWebhookReceived::class,
            'config' => $config,
        ],
    ];
}

// =============================================================================
// Handle Webhook Tests
// =============================================================================

describe('handle', function () {
    it('returns 404 for non-existent webhook path', function () {
        $response = $this->postJson('/autobuilder/webhook/non-existent-path');

        $response->assertNotFound();
        $response->assertJson(['error' => 'Webhook not found']);
    });

    it('returns 404 for inactive flow', function () {
        Flow::create([
            'name' => 'Inactive Webhook Flow',
            'nodes' => [webhookTriggerNode('my-webhook')],
            'edges' => [],
            'active' => false,
        ]);

        $response = $this->postJson('/autobuilder/webhook/my-webhook');

        $response->assertNotFound();
    });

    it('processes webhook for active flow', function () {
        Flow::create([
            'name' => 'Active Webhook Flow',
            'nodes' => [webhookTriggerNode('active-webhook')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->postJson('/autobuilder/webhook/active-webhook', [
            'event' => 'test',
            'data' => ['key' => 'value'],
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure([
            'status',
            'run_id',
        ]);
        expect($response->json('status'))->toBe('accepted');
    });

    it('rejects webhook with invalid secret', function () {
        Flow::create([
            'name' => 'Secret Webhook Flow',
            'nodes' => [webhookTriggerNode('secret-webhook', 'correct-secret')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->postJson('/autobuilder/webhook/secret-webhook', [], [
            'X-Webhook-Secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid webhook signature']);
    });

    it('accepts webhook with valid secret', function () {
        Flow::create([
            'name' => 'Valid Secret Webhook Flow',
            'nodes' => [webhookTriggerNode('valid-secret-webhook', 'my-secret')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->postJson('/autobuilder/webhook/valid-secret-webhook', [
            'data' => 'test',
        ], [
            'X-Webhook-Secret' => 'my-secret',
        ]);

        $response->assertStatus(202);
    });

    it('accepts GET requests', function () {
        Flow::create([
            'name' => 'GET Webhook Flow',
            'nodes' => [webhookTriggerNode('get-webhook', null, 'GET')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->get('/autobuilder/webhook/get-webhook?param=value');

        $response->assertStatus(202);
    });

    it('accepts PUT requests', function () {
        Flow::create([
            'name' => 'PUT Webhook Flow',
            'nodes' => [webhookTriggerNode('put-webhook', null, 'PUT')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->putJson('/autobuilder/webhook/put-webhook', [
            'data' => 'updated',
        ]);

        $response->assertStatus(202);
    });

    it('handles nested webhook paths', function () {
        Flow::create([
            'name' => 'Nested Webhook Flow',
            'nodes' => [webhookTriggerNode('nested/path/webhook')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->postJson('/autobuilder/webhook/nested/path/webhook');

        $response->assertStatus(202);
    });
});

// =============================================================================
// Rate Limiting Tests
// =============================================================================

describe('rate limiting', function () {
    it('returns 429 when rate limit exceeded', function () {
        // Set a very low rate limit for testing
        config(['autobuilder.rate_limiting.webhooks.max_attempts' => 2]);

        Flow::create([
            'name' => 'Rate Limited Flow',
            'nodes' => [webhookTriggerNode('rate-limited-webhook')],
            'edges' => [],
            'active' => true,
        ]);

        // First two requests should succeed
        $this->postJson('/autobuilder/webhook/rate-limited-webhook')->assertStatus(202);
        $this->postJson('/autobuilder/webhook/rate-limited-webhook')->assertStatus(202);

        // Third request should be rate limited
        $response = $this->postJson('/autobuilder/webhook/rate-limited-webhook');
        $response->assertStatus(429);
    });

    it('includes rate limit headers', function () {
        config(['autobuilder.rate_limiting.webhooks.max_attempts' => 60]);

        Flow::create([
            'name' => 'Headers Flow',
            'nodes' => [webhookTriggerNode('headers-webhook')],
            'edges' => [],
            'active' => true,
        ]);

        $response = $this->postJson('/autobuilder/webhook/headers-webhook');

        $response->assertStatus(202);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    });

    it('does not rate limit when disabled', function () {
        config(['autobuilder.rate_limiting.enabled' => false]);

        Flow::create([
            'name' => 'No Rate Limit Flow',
            'nodes' => [webhookTriggerNode('no-rate-limit-webhook')],
            'edges' => [],
            'active' => true,
        ]);

        // Many requests should all succeed
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/autobuilder/webhook/no-rate-limit-webhook')->assertStatus(202);
        }
    });
});
