<?php

namespace App\Http\Requests\Pecuaria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProducaoPecuariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stationId' => ['required', 'uuid', 'exists:estacoes,id'],
            'productType' => ['sometimes', Rule::in(['Leite', 'Ovos', 'Carne', 'Mel', 'Outro'])],
            'productionDate' => ['nullable', 'date'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'recordedBy' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
