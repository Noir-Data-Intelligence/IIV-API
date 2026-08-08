<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;

class AnimalEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'eventType' => ['sometimes', 'string', 'max:100'],
            'eventDate' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
