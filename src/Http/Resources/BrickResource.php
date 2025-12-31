<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrickResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'class' => $this['class'],
            'type' => $this['type'],
            'name' => $this['name'],
            'description' => $this['description'],
            'icon' => $this['icon'],
            'category' => $this['category'],
            'fields' => $this['fields'],
        ];
    }
}
