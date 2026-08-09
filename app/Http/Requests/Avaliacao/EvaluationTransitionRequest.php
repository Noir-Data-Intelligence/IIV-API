<?php

namespace App\Http\Requests\Avaliacao;

use Illuminate\Foundation\Http\FormRequest;

class EvaluationTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sem regras: `actorId` deixou de vir do payload (era forjável — um
     * utilizador podia atribuir a aprovação/validação a outra pessoa, ver
     * auditoria de segurança de 2026-08-09). O actor é sempre
     * `$request->user()->id` no controller.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
