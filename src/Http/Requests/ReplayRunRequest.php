<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplayRunRequest extends FormRequest
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
            'node' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'node.string' => 'Node must be a node ID.',
        ];
    }
}
