<?php

namespace App\Http\Requests\Conta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['receita', 'despesa'])],
            'description' => ['nullable', 'string'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
