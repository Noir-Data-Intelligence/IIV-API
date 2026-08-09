<?php

namespace App\Http\Requests\Investigacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['activa', 'suspensa', 'concluida'])],
            'createdBy' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
