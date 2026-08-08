<?php

namespace App\Http\Requests\Boletim;

use Illuminate\Foundation\Http\FormRequest;

class ResultadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resultado' => ['required', 'array'],
        ];
    }
}
