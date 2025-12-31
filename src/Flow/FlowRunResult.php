<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Flow;

use Throwable;

class FlowRunResult
{
    public function __construct(
        public readonly string $status,
        public readonly FlowContext $context,
        public readonly ?Throwable $error = null
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'run_id' => $this->context->runId,
            'flow_id' => $this->context->flowId,
            'logs' => $this->context->logs,
            'variables' => $this->context->variables,
            'error' => $this->error?->getMessage(),
        ];
    }
}
