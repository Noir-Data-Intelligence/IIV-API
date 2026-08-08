<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoRequest extends FormRequest
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
            'productType' => ['required', Rule::in(['vacina', 'soro', 'reagente'])],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:50'],
            'isArchived' => ['sometimes', 'boolean'],
        ];
    }
}
