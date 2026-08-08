<?php

namespace App\Http\Requests\Plano;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'productId' => ['required', 'uuid', 'exists:produtos,id'],
            'plannedQuantity' => ['required', 'integer', 'min:0'],
            'actualQuantity' => ['nullable', 'integer', 'min:0'],
            'plannedStart' => ['required', 'date'],
            'plannedEnd' => ['required', 'date', 'after_or_equal:plannedStart'],
            'status' => [Rule::in(['planeada', 'em_producao', 'concluida', 'suspensa'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
