<?php

namespace App\Http\Requests\Processo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'typeId' => ['required', 'uuid', 'exists:process_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['baixa', 'normal', 'alta', 'urgente'])],
            'dueDate' => ['nullable', 'date'],
            'requesterId' => ['required', 'uuid', 'exists:users,id'],
            'linkedEntityType' => ['nullable', 'string', 'max:100'],
            'linkedEntityId' => ['nullable', 'uuid'],
        ];
    }
}
