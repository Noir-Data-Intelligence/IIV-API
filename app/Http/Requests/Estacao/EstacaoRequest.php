<?php

namespace App\Http\Requests\Estacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstacaoRequest extends FormRequest
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
            'stationType' => ['sometimes', Rule::in(['zootecnica', 'experimental', 'campo'])],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
