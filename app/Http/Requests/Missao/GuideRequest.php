<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;

class GuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'guideNumber' => ['required', 'string', 'max:50'],
            'issueDate' => ['required', 'date'],
            'perDiem' => ['sometimes', 'numeric', 'min:0'],
            'transport' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
