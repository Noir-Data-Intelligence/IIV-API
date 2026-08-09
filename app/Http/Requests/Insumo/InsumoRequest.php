<?php

namespace App\Http\Requests\Insumo;

use Illuminate\Foundation\Http\FormRequest;

class InsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'laboratoryId' => ['required', 'uuid', 'exists:laboratorios,id'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'minStock' => ['sometimes', 'integer', 'min:0'],
            'expiryDate' => ['nullable', 'date'],
        ];
    }
}
