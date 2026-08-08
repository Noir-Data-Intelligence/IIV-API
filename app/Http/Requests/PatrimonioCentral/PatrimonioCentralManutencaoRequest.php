<?php

namespace App\Http\Requests\PatrimonioCentral;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatrimonioCentralManutencaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assetId' => ['required', 'uuid', 'exists:patrimonio_central,id'],
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['preventiva', 'correctiva', 'inspeccao', 'calibracao'])],
            'description' => ['required', 'string'],
            'cost' => ['sometimes', 'numeric', 'min:0'],
            'provider' => ['nullable', 'string', 'max:255'],
            'nextDueDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
