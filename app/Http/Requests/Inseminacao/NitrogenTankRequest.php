<?php

namespace App\Http\Requests\Inseminacao;

use Illuminate\Foundation\Http\FormRequest;

class NitrogenTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'centerId' => ['required', 'uuid', 'exists:ia_centers,id'],
            'code' => ['sometimes', 'string', 'max:100'],
            'capacityL' => ['sometimes', 'numeric', 'min:0'],
            'currentLevelL' => ['sometimes', 'numeric', 'min:0'],
            'minLevelL' => ['sometimes', 'numeric', 'min:0'],
            // A data da última recarga é sempre um facto passado — nunca pode
            // ser futura (auditoria funcional, secção 15 — Tanques N₂).
            'lastRefillDate' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
