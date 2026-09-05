<?php

namespace App\Http\Requests\Legislacao;

use App\Enums\LegislacaoTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LegislacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Ao contrário de código de laboratório/lote/brinco, `num` NÃO é
            // gerado automaticamente: é o número OFICIAL do diploma legal
            // (ex. "Lei nº 12/2020"), atribuído pela entidade que o publica —
            // o sistema não pode inventar um número sequencial próprio sem
            // tornar a citação legal factualmente incorrecta.
            'num' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['required', Rule::in(array_column(LegislacaoTipo::cases(), 'value'))],
            'ano' => ['required', 'string', 'max:10'],
            'pdfUrl' => ['nullable', 'string'],
            'published' => ['boolean'],
        ];
    }
}
