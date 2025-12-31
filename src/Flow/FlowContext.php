<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Flow;

use Carbon\Carbon;
use Illuminate\Support\Str;

class FlowContext
{
    public string $flowId;

    public string $runId;

    public array $payload;

    public array $variables = [];

    public array $logs = [];

    public bool $paused = false;

    public ?string $pausedAt = null;

    public Carbon $startedAt;

    /**
     * Gate inputs: gateId => [sourceNodeId => bool result]
     * Used to collect condition results before evaluating gates
     */
    public array $gateInputs = [];

    public function __construct(string $flowId, array $payload = [], ?string $runId = null, ?Carbon $startedAt = null)
    {
        $this->flowId = $flowId;
        $this->runId = $runId ?? (string) Str::ulid();
        $this->payload = $payload;
        $this->startedAt = $startedAt ?? now();
    }

    /**
     * Get a value from variables or payload using dot notation
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // First check variables, then payload
        return data_get($this->variables, $key)
            ?? data_get($this->payload, $key)
            ?? $default;
    }

    /**
     * Set a variable
     */
    public function set(string $key, mixed $value): static
    {
        data_set($this->variables, $key, $value);

        return $this;
    }

    /**
     * Check if a key exists in variables or payload
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Add a log entry
     */
    public function log(string $message, string $level = 'info', array $data = []): static
    {
        $this->logs[] = [
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
            'data' => $data,
        ];

        return $this;
    }

    /**
     * Add an info log
     */
    public function info(string $message, array $data = []): static
    {
        return $this->log($message, 'info', $data);
    }

    /**
     * Add a warning log
     */
    public function warning(string $message, array $data = []): static
    {
        return $this->log($message, 'warning', $data);
    }

    /**
     * Add an error log
     */
    public function error(string $message, array $data = []): static
    {
        return $this->log($message, 'error', $data);
    }

    /**
     * Mark flow as paused
     */
    public function setPaused(bool $paused, ?string $nodeId = null): static
    {
        $this->paused = $paused;
        $this->pausedAt = $nodeId;

        return $this;
    }

    /**
     * Check if flow is paused
     */
    public function isPaused(): bool
    {
        return $this->paused;
    }

    /**
     * Record a condition result for a gate input
     */
    public function recordGateInput(string $gateId, string $sourceNodeId, bool $result): static
    {
        if (! isset($this->gateInputs[$gateId])) {
            $this->gateInputs[$gateId] = [];
        }

        $this->gateInputs[$gateId][$sourceNodeId] = $result;

        return $this;
    }

    /**
     * Get all recorded inputs for a gate
     */
    public function getGateInputs(string $gateId): array
    {
        return $this->gateInputs[$gateId] ?? [];
    }

    /**
     * Check if gate has received all expected inputs
     */
    public function hasAllGateInputs(string $gateId, int $expectedCount): bool
    {
        return count($this->getGateInputs($gateId)) >= $expectedCount;
    }

    /**
     * Clear gate inputs after evaluation
     */
    public function clearGateInputs(string $gateId): static
    {
        unset($this->gateInputs[$gateId]);

        return $this;
    }

    /**
     * Merge additional payload data
     */
    public function merge(array $data): static
    {
        $this->payload = array_merge($this->payload, $data);

        return $this;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'flow_id' => $this->flowId,
            'run_id' => $this->runId,
            'payload' => $this->payload,
            'variables' => $this->variables,
            'logs' => $this->logs,
            'paused' => $this->paused,
            'paused_at' => $this->pausedAt,
            'started_at' => $this->startedAt->toIso8601String(),
            'gate_inputs' => $this->gateInputs,
        ];
    }

    /**
     * Restore from array
     */
    public static function fromArray(array $data): static
    {
        $context = new static(
            $data['flow_id'],
            $data['payload'],
            $data['run_id'],
            Carbon::parse($data['started_at'])
        );

        $context->variables = $data['variables'] ?? [];
        $context->logs = $data['logs'] ?? [];
        $context->paused = $data['paused'] ?? false;
        $context->pausedAt = $data['paused_at'] ?? null;
        $context->gateInputs = $data['gate_inputs'] ?? [];

        return $context;
    }
}
