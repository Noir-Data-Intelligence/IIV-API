<?php

namespace App\Http\Requests\Missao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MissaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];

        // `status` só é validado no update — no store, `MissaoController::store()`
        // ignora por completo o campo e força sempre 'planeada' (não vem do
        // cliente). Regressão corrigida: o formulário de criação enviava
        // `status: 'planeada'` por omissão, mas essa regra só permitia
        // 'em_curso'/'concluida'/'cancelada', rejeitando toda e qualquer
        // criação com 422 antes de chegar ao controller.
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Só os 3 estados sem endpoint de transição dedicado (ver
            // MissionWorkflowService) — "planeada"/"submetida"/"aprovada"
            // só são alcançáveis via submit()/approve()/reject(). Sem esta
            // restrição, o PUT genérico saltava a aprovação por completo
            // (auditoria de segurança, 2026-08-09).
            $rules['status'] = ['sometimes', Rule::in(['em_curso', 'concluida', 'cancelada'])];
        }

        return $rules;
    }
}
