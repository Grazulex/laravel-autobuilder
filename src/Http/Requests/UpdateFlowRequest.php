<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'nodes' => 'nullable|array',
            'nodes.*.id' => 'required_with:nodes|string',
            'nodes.*.type' => 'required_with:nodes|string',
            'edges' => 'nullable|array',
            'edges.*.source' => 'required_with:edges|string',
            'edges.*.target' => 'required_with:edges|string',
            'viewport' => 'nullable|array',
            'viewport.x' => 'numeric',
            'viewport.y' => 'numeric',
            'viewport.zoom' => 'numeric|min:0.1|max:10',
            'active' => 'nullable|boolean',
            'sync' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Flow name cannot exceed 255 characters.',
            'description.max' => 'Flow description cannot exceed 5000 characters.',
            'viewport.zoom.min' => 'Zoom level must be at least 0.1.',
            'viewport.zoom.max' => 'Zoom level cannot exceed 10.',
        ];
    }
}
