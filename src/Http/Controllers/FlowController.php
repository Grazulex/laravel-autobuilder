<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Flow\FlowValidator;
use Grazulex\AutoBuilder\Http\Requests\ImportFlowRequest;
use Grazulex\AutoBuilder\Http\Requests\StoreFlowRequest;
use Grazulex\AutoBuilder\Http\Requests\UpdateFlowRequest;
use Grazulex\AutoBuilder\Http\Resources\FlowCollection;
use Grazulex\AutoBuilder\Http\Resources\FlowResource;
use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FlowController extends Controller
{
    public function dashboard()
    {
        return view('autobuilder::index');
    }

    public function index(Request $request)
    {
        $flows = Flow::query()
            ->when($request->has('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($request->has('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->withCount('runs')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return new FlowCollection($flows);
    }

    public function store(StoreFlowRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $flow = Flow::create([
            ...$validated,
            'active' => $validated['active'] ?? false,
            'sync' => $validated['sync'] ?? false,
            'created_by' => auth()->id(),
        ]);

        return (new FlowResource($flow))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Flow $flow): FlowResource
    {
        $flow->loadCount('runs');

        return new FlowResource($flow);
    }

    public function edit(Flow $flow)
    {
        return view('autobuilder::editor', compact('flow'));
    }

    public function update(UpdateFlowRequest $request, Flow $flow): FlowResource
    {
        $validated = $request->validated();

        $flow->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return new FlowResource($flow->fresh());
    }

    public function destroy(Flow $flow): JsonResponse
    {
        $flow->delete();

        return response()->json(['message' => 'Flow deleted'], 200);
    }

    public function duplicate(Flow $flow): JsonResponse
    {
        $clone = $flow->duplicate();

        return (new FlowResource($clone))
            ->response()
            ->setStatusCode(201);
    }

    public function activate(Flow $flow): FlowResource
    {
        $flow->activate();

        return new FlowResource($flow->fresh());
    }

    public function deactivate(Flow $flow): FlowResource
    {
        $flow->deactivate();

        return new FlowResource($flow->fresh());
    }

    public function validate(Flow $flow, FlowValidator $validator): JsonResponse
    {
        $result = $validator->validate($flow);

        return response()->json($result->toArray());
    }

    public function export(Flow $flow): JsonResponse
    {
        return response()->json([
            'data' => $flow->export(),
        ]);
    }

    public function import(ImportFlowRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $flow = Flow::import($validated);

        return (new FlowResource($flow))
            ->response()
            ->setStatusCode(201);
    }
}
