<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExecutionResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'run_id' => $this->context->runId,
            'flow_id' => $this->context->flowId,
            'output' => $this->context->variables,
            'logs' => $this->context->logs,
            'paused' => $this->context->isPaused(),
            'paused_at' => $this->context->pausedAt,
            'error' => $this->error ? [
                'message' => $this->error->getMessage(),
                'code' => $this->error->getCode(),
            ] : null,
            'duration_ms' => $this->context->startedAt->diffInMilliseconds(now()),
        ];
    }
}
