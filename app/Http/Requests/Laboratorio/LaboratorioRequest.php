<?php

namespace App\Http\Requests\Laboratorio;

use Illuminate\Foundation\Http\FormRequest;

class LaboratorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('laboratorio')?->id;

        return [
            'code' => ['required', 'string', 'max:20', 'unique:laboratorios,code,'.$id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'validadorCount' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'slaHoras' => ['nullable', 'integer', 'min:1'],
            'validadoresNomeados' => ['nullable', 'array'],
            'validadoresNomeados.*' => ['uuid', 'exists:users,id'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
