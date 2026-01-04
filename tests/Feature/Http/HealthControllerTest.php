<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;

// =============================================================================
// Basic Health Check Tests
// =============================================================================

describe('basic health check', function () {
    it('returns ok status', function () {
        $response = $this->getJson('/autobuilder/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'service',
            'timestamp',
        ]);
        expect($response->json('status'))->toBe('ok');
        expect($response->json('service'))->toBe('autobuilder');
    });

    it('includes timestamp', function () {
        $response = $this->getJson('/autobuilder/health');

        $response->assertOk();
        expect($response->json('timestamp'))->not->toBeEmpty();
    });
});

// =============================================================================
// Detailed Health Check Tests
// =============================================================================

describe('detailed health check', function () {
    it('returns detailed checks', function () {
        $response = $this->getJson('/autobuilder/health/detailed');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'service',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'bricks',
            ],
        ]);
    });

    it('checks database connection', function () {
        $response = $this->getJson('/autobuilder/health/detailed');

        $response->assertOk();
        expect($response->json('checks.database.status'))->toBe('ok');
    });

    it('checks cache', function () {
        $response = $this->getJson('/autobuilder/health/detailed');

        $response->assertOk();
        expect($response->json('checks.cache.status'))->toBe('ok');
    });

    it('checks bricks registry', function () {
        $response = $this->getJson('/autobuilder/health/detailed');

        $response->assertOk();
        expect($response->json('checks.bricks.status'))->toBe('ok');
        expect($response->json('checks.bricks.counts'))->toHaveKeys(['triggers', 'conditions', 'actions']);
    });
});

// =============================================================================
// Statistics Tests
// =============================================================================

describe('statistics', function () {
    it('returns statistics structure', function () {
        $response = $this->getJson('/autobuilder/health/stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'statistics' => [
                'flows',
                'runs',
                'bricks',
            ],
        ]);
    });

    it('returns flow statistics', function () {
        Flow::factory()->count(3)->create();
        Flow::factory()->active()->count(2)->create();

        $response = $this->getJson('/autobuilder/health/stats');

        $response->assertOk();
        expect($response->json('statistics.flows.total'))->toBe(5);
        expect($response->json('statistics.flows.active'))->toBe(2);
        expect($response->json('statistics.flows.inactive'))->toBe(3);
    });

    it('returns run statistics', function () {
        $flow = Flow::factory()->create();
        FlowRun::factory()->forFlow($flow)->completed()->count(3)->create();
        FlowRun::factory()->forFlow($flow)->failed()->count(2)->create();

        $response = $this->getJson('/autobuilder/health/stats');

        $response->assertOk();
        expect($response->json('statistics.runs.total'))->toBe(5);
        expect($response->json('statistics.runs.by_status.completed'))->toBe(3);
        expect($response->json('statistics.runs.by_status.failed'))->toBe(2);
    });

    it('returns brick counts', function () {
        $response = $this->getJson('/autobuilder/health/stats');

        $response->assertOk();
        expect($response->json('statistics.bricks.triggers'))->toBeGreaterThan(0);
        expect($response->json('statistics.bricks.conditions'))->toBeGreaterThan(0);
        expect($response->json('statistics.bricks.actions'))->toBeGreaterThan(0);
    });

    it('returns today and this week run counts', function () {
        $flow = Flow::factory()->create();
        FlowRun::factory()->forFlow($flow)->count(2)->create();

        $response = $this->getJson('/autobuilder/health/stats');

        $response->assertOk();
        expect($response->json('statistics.runs.today'))->toBe(2);
        expect($response->json('statistics.runs.this_week'))->toBe(2);
    });
});

// =============================================================================
// Public Access Tests
// =============================================================================

describe('public access', function () {
    it('health endpoint is accessible without auth', function () {
        $response = $this->getJson('/autobuilder/health');

        $response->assertOk();
    });

    it('detailed endpoint is accessible without auth', function () {
        $response = $this->getJson('/autobuilder/health/detailed');

        $response->assertOk();
    });

    it('stats endpoint is accessible without auth', function () {
        $response = $this->getJson('/autobuilder/health/stats');

        $response->assertOk();
    });
});
