<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'itemId' => ['required', 'uuid', 'exists:stock_items,id'],
            'type' => ['sometimes', Rule::in(['entrada', 'saida', 'transferencia', 'ajuste'])],
            'quantity' => ['required', 'numeric', 'min:0'],
            'fromLocationId' => ['nullable', 'uuid', 'exists:stock_locations,id'],
            'toLocationId' => ['nullable', 'uuid', 'exists:stock_locations,id'],
            'reason' => ['nullable', 'string'],
            'performedBy' => ['nullable', 'string', 'max:255'],
            'movementDate' => ['nullable', 'date'],
        ];
    }
}
