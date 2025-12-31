<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Flow;

/**
 * Flow Validation Result - Contains validation results for a flow.
 */
class FlowValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly string $flowId,
    ) {}

    /**
     * Check if flow is valid (no errors).
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Check if flow has warnings.
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    /**
     * Check if flow has errors.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Get error count.
     */
    public function errorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get warning count.
     */
    public function warningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Get errors for a specific node.
     */
    public function errorsForNode(string $nodeId): array
    {
        return array_filter($this->errors, fn ($e) => ($e['node_id'] ?? null) === $nodeId);
    }

    /**
     * Get warnings for a specific node.
     */
    public function warningsForNode(string $nodeId): array
    {
        return array_filter($this->warnings, fn ($w) => ($w['node_id'] ?? null) === $nodeId);
    }

    /**
     * Convert to array for API response.
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'flow_id' => $this->flowId,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'summary' => [
                'error_count' => $this->errorCount(),
                'warning_count' => $this->warningCount(),
            ],
        ];
    }

    /**
     * Get a summary message.
     */
    public function getSummary(): string
    {
        if ($this->valid && ! $this->hasWarnings()) {
            return 'Flow is valid and ready to activate.';
        }

        if ($this->valid) {
            return sprintf(
                'Flow is valid with %d warning(s).',
                $this->warningCount()
            );
        }

        return sprintf(
            'Flow has %d error(s) and %d warning(s).',
            $this->errorCount(),
            $this->warningCount()
        );
    }
}
