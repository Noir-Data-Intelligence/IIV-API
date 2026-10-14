<?php

namespace App\Http\Requests\Lab;

use App\Enums\TipoAmostra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExameCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('exame')?->id;

        return [
            'laboratorioId' => ['required', 'uuid', 'exists:laboratorios,id'],
            'codigo' => ['required', 'string', 'max:50', Rule::unique('exames_catalogo', 'codigo')->ignore($id)],
            'nome' => ['required', 'string', 'max:255'],
            'tecnicaMetodo' => ['nullable', 'string', 'max:255'],
            'popCodigo' => ['nullable', 'string', 'max:100'],
            'tiposAmostra' => ['nullable', 'array'],
            'tiposAmostra.*' => [Rule::in(array_column(TipoAmostra::cases(), 'value'))],
            'slaHoras' => ['nullable', 'integer', 'min:1'],
            'camposResultado' => ['nullable', 'array'],
            'camposResultado.*.chave' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'camposResultado.*.tipo' => ['required', Rule::in(['numero', 'texto', 'booleano', 'opcao'])],
            'camposResultado.*.rotulo' => ['nullable', 'string', 'max:255'],
            'camposResultado.*.unidade' => ['nullable', 'string', 'max:50'],
            'camposResultado.*.obrigatorio' => ['nullable', 'boolean'],
            'camposResultado.*.opcoes' => ['nullable', 'array'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
