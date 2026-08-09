<?php

namespace App\Http\Requests\ProcessEvent;

use Illuminate\Foundation\Http\FormRequest;

class ProcessEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `actorId` deixou de vir do payload — ver ProcessTransitionRequest.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'eventType' => ['required', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
