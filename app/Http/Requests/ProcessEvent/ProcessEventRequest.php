<?php

namespace App\Http\Requests\ProcessEvent;

use Illuminate\Foundation\Http\FormRequest;

class ProcessEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'eventType' => ['required', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
            'actorId' => ['nullable', 'uuid'],
        ];
    }
}
