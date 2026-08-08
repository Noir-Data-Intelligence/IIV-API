<?php

namespace App\Http\Requests\Inseminacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InseminationRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'animalId' => ['required', 'uuid', 'exists:animals,id'],
            'doseId' => ['nullable', 'uuid', 'exists:semen_doses,id'],
            'technicianId' => ['nullable', 'uuid', 'exists:users,id'],
            'inseminationDate' => ['required', 'date'],
            'result' => ['sometimes', Rule::in(['pendente', 'confirmada', 'falhou'])],
            'pregnancyConfirmedAt' => ['nullable', 'date'],
            'expectedBirthDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
