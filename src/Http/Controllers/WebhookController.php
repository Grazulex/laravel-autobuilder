<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Support\WebhookPathNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected FlowRunner $runner
    ) {}

    public function handle(Request $request, string $path): JsonResponse|Response
    {
        $normalizedPath = WebhookPathNormalizer::normalize($path);

        $flow = Flow::where('webhook_path', $normalizedPath)
            ->where('active', true)
            ->first();

        if (! $flow) {
            Log::debug('AutoBuilder webhook not found', [
                'original_path' => $path,
                'normalized_path' => $normalizedPath,
            ]);

            return response()->json(['error' => 'Webhook not found'], 404);
        }

        // Validate HTTP method if configured (skip for 'ANY')
        $allowedMethod = $flow->trigger_config['method'] ?? 'POST';
        if ($allowedMethod !== 'ANY' && strtoupper($request->method()) !== strtoupper($allowedMethod)) {
            return response()->json([
                'error' => 'Method not allowed',
                'allowed' => $allowedMethod,
            ], 405);
        }

        // Verify secret if configured
        $secret = $flow->trigger_config['secret'] ?? null;
        if ($secret) {
            $signature = $request->header(config('autobuilder.security.webhook_signature_header', 'X-Webhook-Secret'));

            if (! hash_equals($secret, (string) $signature)) {
                return response()->json(['error' => 'Invalid webhook signature'], 401);
            }
        }

        // Build enriched payload (backward compatible + namespaced)
        $payload = [
            // New namespaced context
            'webhook' => [
                'method' => $request->method(),
                'path' => $normalizedPath,
                'query' => $request->query(),
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
                'content_type' => $request->header('Content-Type'),
                'user_agent' => $request->userAgent(),
            ],
            // Backward compatible flat keys
            'method' => $request->method(),
            'path' => $normalizedPath,
            'query' => $request->query(),
            'body' => $request->all(),
            'headers' => $request->headers->all(),
        ];

        $result = $this->runner->run($flow, $payload);

        // Check for custom webhook response from WebhookAnswer action
        if ($result->context->hasWebhookResponse()) {
            $webhookResponse = $result->context->getWebhookResponse();

            return response(
                $webhookResponse['body'] ?? '',
                $webhookResponse['status_code'] ?? 200,
            )->withHeaders(array_merge(
                ['Content-Type' => $webhookResponse['content_type'] ?? 'application/json'],
                $webhookResponse['headers'] ?? [],
            ));
        }

        return response()->json([
            'status' => 'accepted',
            'run_id' => $result->context->runId,
        ], 202);
    }
}
