<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportFlowRequest extends FormRequest
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
            'nodes' => 'required|array',
            'nodes.*.id' => 'required|string',
            'nodes.*.type' => 'required|string',
            'nodes.*.data' => 'required|array',
            'nodes.*.position' => 'required|array',
            'nodes.*.position.x' => 'required|numeric',
            'nodes.*.position.y' => 'required|numeric',
            'edges' => 'required|array',
            'edges.*.id' => 'required_with:edges|string',
            'edges.*.source' => 'required_with:edges|string',
            'edges.*.target' => 'required_with:edges|string',
            'viewport' => 'nullable|array',
            'viewport.x' => 'numeric',
            'viewport.y' => 'numeric',
            'viewport.zoom' => 'numeric|min:0.1|max:10',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A flow name is required for import.',
            'nodes.required' => 'Nodes data is required for import.',
            'edges.required' => 'Edges data is required for import.',
        ];
    }
}
