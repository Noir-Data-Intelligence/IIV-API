<?php

namespace App\Http\Requests\Agricultura;

use Illuminate\Foundation\Http\FormRequest;

class CropRequest extends FormRequest
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
            'scientificName' => ['nullable', 'string', 'max:255'],
            'cycleDays' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
