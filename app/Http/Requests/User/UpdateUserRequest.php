<?php

namespace App\Http\Requests\User;

use App\Enums\AppRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Espelha `UpdateUserPayload` em Front-end/src/types/dto/user.ts — todos os campos opcionais. */
class UpdateUserRequest extends FormRequest
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
            'roles' => ['sometimes', 'array'],
            'roles.*' => [Rule::in(AppRole::values())],
            'departmentIds' => ['sometimes', 'array'],
            'departmentIds.*' => ['uuid', 'exists:departments,id'],
        ];
    }
}
