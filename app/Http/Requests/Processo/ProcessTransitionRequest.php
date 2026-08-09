<?php

namespace App\Http\Requests\Processo;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `actorId` deixou de vir do payload — era forjável (um utilizador
     * podia atribuir a decisão BPM a outra pessoa, auditoria de segurança
     * 2026-08-09). O actor é sempre `$request->user()->id` no controller.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
