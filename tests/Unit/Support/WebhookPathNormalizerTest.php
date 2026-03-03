<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Support\WebhookPathNormalizer;

describe('normalize', function () {
    it('returns null for null input', function () {
        expect(WebhookPathNormalizer::normalize(null))->toBeNull();
    });

    it('returns null for empty string', function () {
        expect(WebhookPathNormalizer::normalize(''))->toBeNull();
    });

    it('returns null for whitespace-only string', function () {
        expect(WebhookPathNormalizer::normalize('   '))->toBeNull();
    });

    it('converts to lowercase', function () {
        expect(WebhookPathNormalizer::normalize('My-Webhook'))->toBe('my-webhook');
    });

    it('trims whitespace', function () {
        expect(WebhookPathNormalizer::normalize('  my-webhook  '))->toBe('my-webhook');
    });

    it('trims leading and trailing slashes', function () {
        expect(WebhookPathNormalizer::normalize('/my-webhook/'))->toBe('my-webhook');
    });

    it('slugifies path segments', function () {
        expect(WebhookPathNormalizer::normalize('My Webhook Path'))->toBe('my-webhook-path');
    });

    it('handles nested paths', function () {
        expect(WebhookPathNormalizer::normalize('api/v1/my-hook'))->toBe('api/v1/my-hook');
    });

    it('normalizes nested paths with mixed case and spaces', function () {
        expect(WebhookPathNormalizer::normalize('API/V1/My Hook'))->toBe('api/v1/my-hook');
    });

    it('collapses multiple slashes', function () {
        expect(WebhookPathNormalizer::normalize('my///webhook'))->toBe('my/webhook');
    });

    it('handles special characters', function () {
        expect(WebhookPathNormalizer::normalize('my_webhook@test!'))->toBe('my-webhook-at-test');
    });

    it('produces consistent output for various inputs of same intent', function () {
        $inputs = [
            'my-webhook',
            'My-Webhook',
            '/my-webhook/',
            '  my-webhook  ',
            'MY-WEBHOOK',
        ];

        $results = array_map(fn ($p) => WebhookPathNormalizer::normalize($p), $inputs);

        expect(array_unique($results))->toHaveCount(1);
        expect($results[0])->toBe('my-webhook');
    });
});
