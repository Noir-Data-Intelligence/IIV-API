<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'categoryId' => ['nullable', 'uuid', 'exists:doc_categories,id'],
            'ownerId' => ['sometimes', 'uuid', 'exists:users,id'],
            'status' => [Rule::in(['rascunho', 'submetido', 'aprovado', 'rejeitado', 'arquivado'])],
            'visibility' => [Rule::in(['publico', 'departamento', 'privado'])],
            'expiryDate' => ['nullable', 'date'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
