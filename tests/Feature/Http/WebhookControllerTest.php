<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;

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
            'webhook_path' => 'my-webhook',
            'active' => false,
            'nodes' => [],
            'edges' => [],
        ]);

        $response = $this->postJson('/autobuilder/webhook/my-webhook');

        $response->assertNotFound();
    });

    it('processes webhook for active flow', function () {
        Flow::create([
            'name' => 'Active Webhook Flow',
            'webhook_path' => 'active-webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
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
            'webhook_path' => 'secret-webhook',
            'active' => true,
            'trigger_config' => ['secret' => 'correct-secret'],
            'nodes' => [],
            'edges' => [],
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
            'webhook_path' => 'valid-secret-webhook',
            'active' => true,
            'trigger_config' => ['secret' => 'my-secret'],
            'nodes' => [],
            'edges' => [],
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
            'webhook_path' => 'get-webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
        ]);

        $response = $this->get('/autobuilder/webhook/get-webhook?param=value');

        $response->assertStatus(202);
    });

    it('accepts PUT requests', function () {
        Flow::create([
            'name' => 'PUT Webhook Flow',
            'webhook_path' => 'put-webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
        ]);

        $response = $this->putJson('/autobuilder/webhook/put-webhook', [
            'data' => 'updated',
        ]);

        $response->assertStatus(202);
    });

    it('handles nested webhook paths', function () {
        Flow::create([
            'name' => 'Nested Webhook Flow',
            'webhook_path' => 'nested/path/webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
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
            'webhook_path' => 'rate-limited-webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
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
            'webhook_path' => 'headers-webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
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
            'webhook_path' => 'no-rate-limit-webhook',
            'active' => true,
            'nodes' => [],
            'edges' => [],
        ]);

        // Many requests should all succeed
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/autobuilder/webhook/no-rate-limit-webhook')->assertStatus(202);
        }
    });
});
