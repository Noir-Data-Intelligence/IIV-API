<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;

class AnimalHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recordType' => ['sometimes', 'string', 'max:100'],
            'productName' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'veterinarian' => ['nullable', 'string', 'max:255'],
            'recordDate' => ['required', 'date'],
            'nextDueDate' => ['nullable', 'date'],
        ];
    }
}
