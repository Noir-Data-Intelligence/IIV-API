<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;

class ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'userId' => ['required', 'uuid', 'exists:users,id'],
            'role' => ['required', 'string', 'max:100'],
            'perDiem' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
