<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Http\Resources\ExecutionResultResource;
use Grazulex\AutoBuilder\Http\Resources\FlowRunCollection;
use Grazulex\AutoBuilder\Http\Resources\FlowRunResource;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ExecutionController extends Controller
{
    public function __construct(
        protected FlowRunner $runner
    ) {}

    public function test(Request $request, Flow $flow): ExecutionResultResource
    {
        $validated = $request->validate([
            'payload' => 'nullable|array',
        ]);

        $result = $this->runner->run($flow, $validated['payload'] ?? []);

        return new ExecutionResultResource($result);
    }

    public function run(Request $request, Flow $flow): ExecutionResultResource
    {
        if (! $flow->active) {
            abort(422, 'Flow is not active');
        }

        $validated = $request->validate([
            'payload' => 'nullable|array',
        ]);

        $result = $this->runner->run($flow, $validated['payload'] ?? []);

        return new ExecutionResultResource($result);
    }

    public function runs(Request $request, Flow $flow): FlowRunCollection
    {
        $runs = $flow->runs()
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return new FlowRunCollection($runs);
    }

    public function show(FlowRun $run): FlowRunResource
    {
        return new FlowRunResource($run);
    }

    public function logs(FlowRun $run): JsonResponse
    {
        return response()->json([
            'data' => $run->logs,
            'run_id' => $run->id,
            'status' => $run->status,
        ]);
    }
}
