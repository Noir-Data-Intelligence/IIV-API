<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;

class AnimalEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'eventType' => ['sometimes', 'string', 'max:100'],
            'eventDate' => ['required', 'date'],
            // Obrigatório apenas para o evento "transferencia" (ver auditoria
            // funcional — "definir destino da transferência").
            'destination' => ['required_if:eventType,transferencia', 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
