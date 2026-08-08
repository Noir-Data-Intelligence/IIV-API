<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;

class MissionTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'actorId' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
