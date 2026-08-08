<?php

namespace App\Http\Requests\ProcessType;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTypeRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'slaDays' => ['nullable', 'integer', 'min:1'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
