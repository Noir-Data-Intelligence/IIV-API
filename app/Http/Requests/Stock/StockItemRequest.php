<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockItemRequest extends FormRequest
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
            'category' => ['sometimes', Rule::in(['laboratorio', 'vacinas', 'agricola', 'pecuaria', 'administrativo', 'semen', 'combustivel'])],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'quantity' => ['sometimes', 'numeric', 'min:0'],
            'minStock' => ['sometimes', 'numeric', 'min:0'],
            'locationId' => ['nullable', 'uuid', 'exists:stock_locations,id'],
            'expiryDate' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'unitCost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
