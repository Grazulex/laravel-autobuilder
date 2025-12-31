<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flow_id' => $this->flow_id,
            'status' => $this->status,
            'payload' => $this->payload,
            'variables' => $this->variables,
            'logs' => $this->logs,
            'error' => $this->error,
            'duration_ms' => $this->getDurationMs(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function getDurationMs(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->started_at->diffInMilliseconds($this->completed_at);
    }
}
