<?php

namespace App\Http\Requests\Distribuicao;

use Illuminate\Foundation\Http\FormRequest;

class DistribuicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'batchId' => ['required', 'uuid', 'exists:lotes,id'],
            'destination' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'distributionDate' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
