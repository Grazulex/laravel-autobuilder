<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __construct(
        protected FlowRunner $runner
    ) {}

    public function handle(Request $request, string $path): JsonResponse
    {
        $flow = Flow::where('webhook_path', $path)
            ->where('active', true)
            ->first();

        if (! $flow) {
            return response()->json(['error' => 'Webhook not found'], 404);
        }

        // Verify secret if configured
        $secret = $flow->trigger_config['secret'] ?? null;
        if ($secret) {
            $signature = $request->header(config('autobuilder.security.webhook_signature_header', 'X-Webhook-Secret'));

            if (! hash_equals($secret, (string) $signature)) {
                return response()->json(['error' => 'Invalid webhook signature'], 401);
            }
        }

        // Run the flow
        $payload = [
            'method' => $request->method(),
            'path' => $path,
            'query' => $request->query(),
            'body' => $request->all(),
            'headers' => $request->headers->all(),
        ];

        $result = $this->runner->run($flow, $payload);

        return response()->json([
            'status' => 'accepted',
            'run_id' => $result->context->runId,
        ], 202);
    }
}
