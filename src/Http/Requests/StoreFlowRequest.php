<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlowRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'nodes' => 'nullable|array',
            'nodes.*.id' => 'required_with:nodes|string',
            'nodes.*.type' => 'required_with:nodes|string',
            'nodes.*.data' => 'required_with:nodes|array',
            'nodes.*.position' => 'required_with:nodes|array',
            'nodes.*.position.x' => 'required_with:nodes.*.position|numeric',
            'nodes.*.position.y' => 'required_with:nodes.*.position|numeric',
            'edges' => 'nullable|array',
            'edges.*.id' => 'required_with:edges|string',
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
            'name.required' => 'A flow name is required.',
            'name.max' => 'Flow name cannot exceed 255 characters.',
            'description.max' => 'Flow description cannot exceed 5000 characters.',
        ];
    }
}
