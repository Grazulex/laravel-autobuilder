<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Flow;

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

/**
 * Flow Validator - Validates flow configuration before activation.
 *
 * Checks for:
 * - Required fields on nodes
 * - At least one trigger
 * - Orphan nodes (not connected)
 * - Valid connections
 */
class FlowValidator
{
    protected BrickRegistry $registry;

    protected array $errors = [];

    protected array $warnings = [];

    public function __construct(BrickRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Validate a flow and return validation result.
     */
    public function validate(Flow $flow): FlowValidationResult
    {
        $this->errors = [];
        $this->warnings = [];

        $nodes = $flow->nodes ?? [];
        $edges = $flow->edges ?? [];

        // Check for empty flow
        if (empty($nodes)) {
            $this->errors[] = [
                'type' => 'empty_flow',
                'message' => 'Flow has no nodes. Add at least one trigger.',
                'node_id' => null,
            ];

            return $this->buildResult($flow);
        }

        // Build node and edge maps
        $nodeMap = $this->buildNodeMap($nodes);
        $edgeMap = $this->buildEdgeMap($edges);

        // Run validations
        $this->validateTriggers($nodes, $nodeMap);
        $this->validateRequiredFields($nodes, $nodeMap);
        $this->validateOrphanNodes($nodes, $edgeMap);
        $this->validateConnections($edges, $nodeMap);

        return $this->buildResult($flow);
    }

    /**
     * Validate that flow has at least one trigger.
     */
    protected function validateTriggers(array $nodes, array $nodeMap): void
    {
        $triggers = array_filter($nodes, fn ($node) => ($node['type'] ?? '') === 'trigger');

        if (empty($triggers)) {
            $this->errors[] = [
                'type' => 'no_trigger',
                'message' => 'Flow must have at least one trigger node.',
                'node_id' => null,
            ];
        }
    }

    /**
     * Validate required fields on each node.
     */
    protected function validateRequiredFields(array $nodes, array $nodeMap): void
    {
        foreach ($nodes as $node) {
            $nodeId = $node['id'] ?? 'unknown';
            $nodeData = $node['data'] ?? [];
            $brickClass = $nodeData['brick'] ?? null;
            $config = $nodeData['config'] ?? [];
            $fields = $nodeData['fields'] ?? [];
            $label = $nodeData['label'] ?? $nodeId;

            if (! $brickClass) {
                $this->errors[] = [
                    'type' => 'missing_brick',
                    'message' => "Node '{$label}' has no brick class defined.",
                    'node_id' => $nodeId,
                ];

                continue;
            }

            // Get fields from node data or from registry
            if (empty($fields)) {
                try {
                    $brick = $this->registry->resolve($brickClass);
                    if ($brick) {
                        $fields = array_map(fn ($f) => $f->toArray(), $brick->fields());
                    }
                } catch (\Throwable) {
                    // Brick not found in registry
                }
            }

            // Check required fields
            foreach ($fields as $field) {
                $fieldName = $field['name'] ?? '';
                $fieldLabel = $field['label'] ?? $fieldName;
                $isRequired = $field['required'] ?? false;
                $isHidden = $field['hidden'] ?? false;

                // Check visibility condition
                if (isset($field['visibleWhen'])) {
                    $visibleField = $field['visibleWhen']['field'] ?? $field['visibleWhen'];
                    $visibleValue = $field['visibleWhen']['value'] ?? null;

                    // If visibleWhen is a simple string, it's the field name
                    if (is_string($field['visibleWhen'])) {
                        $visibleField = $field['visibleWhen'];
                        $visibleValue = null;
                    }

                    $currentValue = $config[$visibleField] ?? null;

                    // Skip if field is not visible
                    if ($visibleValue !== null && $currentValue !== $visibleValue) {
                        continue;
                    }
                }

                if ($isRequired && ! $isHidden) {
                    $value = $config[$fieldName] ?? null;

                    if ($value === null || $value === '' || $value === []) {
                        $this->errors[] = [
                            'type' => 'required_field',
                            'message' => "Node '{$label}': Required field '{$fieldLabel}' is empty.",
                            'node_id' => $nodeId,
                            'field' => $fieldName,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Check for orphan nodes (not connected to anything).
     */
    protected function validateOrphanNodes(array $nodes, array $edgeMap): void
    {
        foreach ($nodes as $node) {
            $nodeId = $node['id'] ?? 'unknown';
            $nodeType = $node['type'] ?? '';
            $label = $node['data']['label'] ?? $nodeId;

            $hasIncoming = isset($edgeMap['targets'][$nodeId]);
            $hasOutgoing = isset($edgeMap['sources'][$nodeId]);

            // Triggers should have outgoing connections
            if ($nodeType === 'trigger' && ! $hasOutgoing) {
                $this->warnings[] = [
                    'type' => 'orphan_trigger',
                    'message' => "Trigger '{$label}' has no outgoing connections.",
                    'node_id' => $nodeId,
                ];
            }

            // Actions should have incoming connections
            if ($nodeType === 'action' && ! $hasIncoming) {
                $this->warnings[] = [
                    'type' => 'orphan_action',
                    'message' => "Action '{$label}' has no incoming connections (will never execute).",
                    'node_id' => $nodeId,
                ];
            }

            // Conditions should have both
            if ($nodeType === 'condition') {
                if (! $hasIncoming) {
                    $this->warnings[] = [
                        'type' => 'orphan_condition',
                        'message' => "Condition '{$label}' has no incoming connections.",
                        'node_id' => $nodeId,
                    ];
                }
                if (! $hasOutgoing) {
                    $this->warnings[] = [
                        'type' => 'orphan_condition',
                        'message' => "Condition '{$label}' has no outgoing connections.",
                        'node_id' => $nodeId,
                    ];
                }
            }

            // Gates should have multiple incoming and at least one outgoing
            if ($nodeType === 'gate') {
                $incomingCount = count($edgeMap['targets'][$nodeId] ?? []);
                if ($incomingCount < 2) {
                    $this->warnings[] = [
                        'type' => 'gate_inputs',
                        'message' => "Gate '{$label}' has only {$incomingCount} input(s). Gates typically need 2+ inputs.",
                        'node_id' => $nodeId,
                    ];
                }
                if (! $hasOutgoing) {
                    $this->warnings[] = [
                        'type' => 'orphan_gate',
                        'message' => "Gate '{$label}' has no outgoing connections.",
                        'node_id' => $nodeId,
                    ];
                }
            }
        }
    }

    /**
     * Validate edge connections.
     */
    protected function validateConnections(array $edges, array $nodeMap): void
    {
        foreach ($edges as $edge) {
            $sourceId = $edge['source'] ?? null;
            $targetId = $edge['target'] ?? null;

            if (! $sourceId || ! $targetId) {
                continue;
            }

            $sourceNode = $nodeMap[$sourceId] ?? null;
            $targetNode = $nodeMap[$targetId] ?? null;

            if (! $sourceNode) {
                $this->errors[] = [
                    'type' => 'invalid_edge',
                    'message' => "Edge references non-existent source node: {$sourceId}",
                    'node_id' => $sourceId,
                ];
            }

            if (! $targetNode) {
                $this->errors[] = [
                    'type' => 'invalid_edge',
                    'message' => "Edge references non-existent target node: {$targetId}",
                    'node_id' => $targetId,
                ];
            }

            // Check for self-loops
            if ($sourceId === $targetId) {
                $label = $sourceNode['data']['label'] ?? $sourceId;
                $this->warnings[] = [
                    'type' => 'self_loop',
                    'message' => "Node '{$label}' connects to itself (potential infinite loop).",
                    'node_id' => $sourceId,
                ];
            }
        }
    }

    /**
     * Build a map of nodes by ID.
     */
    protected function buildNodeMap(array $nodes): array
    {
        $map = [];
        foreach ($nodes as $node) {
            $map[$node['id'] ?? ''] = $node;
        }

        return $map;
    }

    /**
     * Build edge maps for quick lookup.
     */
    protected function buildEdgeMap(array $edges): array
    {
        $sources = [];
        $targets = [];

        foreach ($edges as $edge) {
            $source = $edge['source'] ?? null;
            $target = $edge['target'] ?? null;

            if ($source) {
                $sources[$source][] = $edge;
            }
            if ($target) {
                $targets[$target][] = $edge;
            }
        }

        return [
            'sources' => $sources,
            'targets' => $targets,
        ];
    }

    /**
     * Build the validation result.
     */
    protected function buildResult(Flow $flow): FlowValidationResult
    {
        return new FlowValidationResult(
            valid: empty($this->errors),
            errors: $this->errors,
            warnings: $this->warnings,
            flowId: (string) $flow->id
        );
    }
}
