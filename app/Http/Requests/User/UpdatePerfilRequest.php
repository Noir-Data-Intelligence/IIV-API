<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/** Espelha `PerfilUpdatePayload` em Front-end/src/types/dto/perfil.ts. */
class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fullName' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'avatarUrl' => ['sometimes', 'nullable', 'string', 'max:2048', 'url'],
        ];
    }
}
