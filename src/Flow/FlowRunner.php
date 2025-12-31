<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Flow;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Bricks\Gate;
use Grazulex\AutoBuilder\Events\BrickExecuted;
use Grazulex\AutoBuilder\Events\BrickFailed;
use Grazulex\AutoBuilder\Events\FlowCompleted;
use Grazulex\AutoBuilder\Events\FlowFailed;
use Grazulex\AutoBuilder\Events\FlowStarted;
use Grazulex\AutoBuilder\Exceptions\FlowException;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FlowRunner
{
    protected BrickRegistry $registry;

    protected Flow $flow;

    protected FlowContext $context;

    protected array $executedNodes = [];

    public function __construct(BrickRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Run a flow
     */
    public function run(Flow $flow, array $payload = [], ?string $startFromNode = null): FlowRunResult
    {
        $this->flow = $flow;
        $this->context = new FlowContext((string) $flow->id, $payload);
        $this->executedNodes = [];

        event(new FlowStarted($flow, $this->context));

        try {
            // Find trigger node(s) or resume point
            $startNodes = $startFromNode
                ? [$this->getNode($startFromNode)]
                : $this->findTriggerNodes();

            foreach ($startNodes as $node) {
                $this->executeNode($node);

                if ($this->context->isPaused()) {
                    break;
                }
            }

            // Check if flow was paused
            if ($this->context->isPaused()) {
                $this->saveFlowState();

                return new FlowRunResult(
                    status: 'paused',
                    context: $this->context
                );
            }

            event(new FlowCompleted($flow, $this->context));

            return new FlowRunResult(
                status: 'completed',
                context: $this->context
            );
        } catch (Throwable $e) {
            $this->context->error($e->getMessage());
            event(new FlowFailed($flow, $this->context, $e));

            return new FlowRunResult(
                status: 'failed',
                context: $this->context,
                error: $e
            );
        } finally {
            $this->saveFlowRun();
        }
    }

    /**
     * Resume a paused flow
     */
    public function resume(string $runId): FlowRunResult
    {
        $data = Cache::get("autobuilder:paused:{$runId}");

        if (! $data) {
            throw new FlowException("Paused flow not found: {$runId}");
        }

        $this->context = FlowContext::fromArray($data);
        $this->flow = Flow::findOrFail($this->context->flowId);

        Cache::forget("autobuilder:paused:{$runId}");

        return $this->run($this->flow, $this->context->payload, $this->context->pausedAt);
    }

    /**
     * Execute a single node
     */
    protected function executeNode(array $node): void
    {
        // Prevent infinite loops
        $nodeExecutionKey = $node['id'] . '_' . count($this->executedNodes);
        if (in_array($node['id'], $this->executedNodes) && count($this->executedNodes) > config('autobuilder.execution.max_nodes', 100)) {
            $this->context->warning("Max node execution limit reached: {$node['id']}");

            return;
        }

        $this->executedNodes[] = $node['id'];

        // Support both flat structure and nested data structure (Vue Flow format)
        $brickClass = $node['data']['brick'] ?? $node['brick'] ?? null;
        $brickConfig = $node['data']['config'] ?? $node['config'] ?? [];

        if (! $brickClass) {
            throw new FlowException("Node {$node['id']} has no brick class defined");
        }

        $brick = $this->registry->resolve($brickClass, $brickConfig);

        $this->context->info("Executing: {$brick->name()}");
        event(new BrickExecuted($brick, $this->context));

        try {
            if ($brick instanceof Gate) {
                $this->executeGate($node, $brick);
            } elseif ($brick instanceof Condition) {
                $this->executeCondition($node, $brick);
            } elseif ($brick instanceof Action) {
                $this->context = $brick->handle($this->context);

                if (! $this->context->isPaused()) {
                    $this->executeNextNodes($node['id']);
                }
            } else {
                // Trigger - just execute next nodes
                $this->executeNextNodes($node['id']);
            }
        } catch (Throwable $e) {
            event(new BrickFailed($brick, $this->context, $e));
            throw $e;
        }
    }

    /**
     * Execute a condition node
     */
    protected function executeCondition(array $node, Condition $condition): void
    {
        $result = $condition->evaluate($this->context);
        $this->context->info("Condition '{$condition->name()}' = " . ($result ? 'true' : 'false'));

        // Find outgoing edges
        $edges = $this->getOutgoingEdges($node['id']);

        // First pass: record results for any connected gates (regardless of edge condition)
        foreach ($edges as $edge) {
            $nextNode = $this->getNode($edge['target']);

            if ($this->isGateNode($nextNode)) {
                // Always record the condition result for gates
                $this->context->recordGateInput($nextNode['id'], $node['id'], $result);

                // Check if gate has all its inputs
                $expectedInputs = $this->countIncomingEdges($nextNode['id']);
                if ($this->context->hasAllGateInputs($nextNode['id'], $expectedInputs)) {
                    $this->executeNode($nextNode);
                }
            }
        }

        // Second pass: follow non-gate edges based on condition result
        foreach ($edges as $edge) {
            $nextNode = $this->getNode($edge['target']);

            // Skip gates (already handled above)
            if ($this->isGateNode($nextNode)) {
                continue;
            }

            // Support both 'condition' (legacy) and 'sourceHandle' (Vue Flow format)
            $edgeCondition = $edge['sourceHandle'] ?? $edge['condition'] ?? null;

            // Determine if this edge should be followed
            $shouldFollow = false;

            // If no condition specified, follow the edge based on result
            if ($edgeCondition === null) {
                $shouldFollow = $result;
            } elseif (
                ($edgeCondition === 'true' && $result) ||
                ($edgeCondition === 'false' && ! $result) ||
                $edgeCondition === 'always'
            ) {
                $shouldFollow = true;
            }

            if ($shouldFollow) {
                $this->executeNode($nextNode);
            }
        }
    }

    /**
     * Execute a gate node
     */
    protected function executeGate(array $node, Gate $gate): void
    {
        $inputs = $this->context->getGateInputs($node['id']);

        // Evaluate the gate
        $result = $gate->evaluate($inputs, $this->context);
        $this->context->info("Gate '{$gate->name()}' = " . ($result ? 'PASS' : 'FAIL'));

        // Clear gate inputs after evaluation
        $this->context->clearGateInputs($node['id']);

        // Find outgoing edges and follow based on result
        $edges = $this->getOutgoingEdges($node['id']);

        foreach ($edges as $edge) {
            $sourceHandle = $edge['sourceHandle'] ?? null;

            if (
                ($sourceHandle === 'true' && $result) ||
                ($sourceHandle === 'false' && ! $result) ||
                $sourceHandle === null && $result // Default: only follow on true if no handle specified
            ) {
                $nextNode = $this->getNode($edge['target']);
                $this->executeNode($nextNode);
            }
        }
    }

    /**
     * Check if a node is a gate
     */
    protected function isGateNode(array $node): bool
    {
        return ($node['type'] ?? '') === 'gate';
    }

    /**
     * Count incoming edges to a node
     */
    protected function countIncomingEdges(string $nodeId): int
    {
        return count(array_filter(
            $this->flow->edges ?? [],
            fn ($edge) => $edge['target'] === $nodeId
        ));
    }

    /**
     * Execute nodes connected to the given node
     */
    protected function executeNextNodes(string $nodeId): void
    {
        $edges = $this->getOutgoingEdges($nodeId);

        foreach ($edges as $edge) {
            $nextNode = $this->getNode($edge['target']);
            $this->executeNode($nextNode);
        }
    }

    /**
     * Get outgoing edges from a node
     */
    protected function getOutgoingEdges(string $nodeId): array
    {
        return array_filter(
            $this->flow->edges ?? [],
            fn ($edge) => $edge['source'] === $nodeId
        );
    }

    /**
     * Get a node by ID
     */
    protected function getNode(string $nodeId): array
    {
        foreach ($this->flow->nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }

        throw new FlowException("Node not found: {$nodeId}");
    }

    /**
     * Find all trigger nodes
     */
    protected function findTriggerNodes(): array
    {
        return array_filter(
            $this->flow->nodes ?? [],
            fn ($node) => ($node['type'] ?? '') === 'trigger'
        );
    }

    /**
     * Save flow run to database
     */
    protected function saveFlowRun(): void
    {
        FlowRun::create([
            'id' => $this->context->runId,
            'flow_id' => $this->flow->id,
            'status' => $this->context->isPaused() ? 'paused' : 'completed',
            'payload' => $this->context->payload,
            'variables' => $this->context->variables,
            'logs' => $this->context->logs,
            'started_at' => $this->context->startedAt,
            'completed_at' => now(),
        ]);
    }

    /**
     * Save paused flow state for resumption
     */
    protected function saveFlowState(): void
    {
        Cache::put(
            "autobuilder:paused:{$this->context->runId}",
            $this->context->toArray(),
            now()->addDays(7)
        );
    }
}
