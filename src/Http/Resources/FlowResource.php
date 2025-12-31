<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'nodes' => $this->nodes ?? [],
            'edges' => $this->edges ?? [],
            'viewport' => $this->viewport,
            'active' => $this->active,
            'sync' => $this->sync,
            'trigger_type' => $this->trigger_type,
            'trigger_config' => $this->trigger_config,
            'webhook_path' => $this->webhook_path,
            'webhook_url' => $this->webhook_path ? url(config('autobuilder.routes.prefix', 'autobuilder').'/webhook/'.$this->webhook_path) : null,
            'nodes_count' => count($this->nodes ?? []),
            'edges_count' => count($this->edges ?? []),
            'runs_count' => $this->whenCounted('runs'),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
