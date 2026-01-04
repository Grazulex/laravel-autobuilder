<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunFlowRequest extends FormRequest
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
            'payload' => 'nullable|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payload.array' => 'Payload must be an array.',
        ];
    }
}
