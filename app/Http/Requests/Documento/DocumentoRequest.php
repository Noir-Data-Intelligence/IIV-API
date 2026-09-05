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
        // No update (ex.: mudança de estado via PUT), o payload pode conter
        // só o campo que muda (ex. `{status}`) — `title` não deve ser
        // reenviado nem exigido nesse caso.
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'categoryId' => ['nullable', 'uuid', 'exists:doc_categories,id'],
            // `ownerId` deixou de ser aceite do payload — era forjável, um
            // utilizador podia criar um documento em nome de outra pessoa
            // (auditoria de segurança, 2026-08-09). O dono é sempre
            // `$request->user()->id` no controller.
            'status' => [Rule::in(['rascunho', 'submetido', 'aprovado', 'rejeitado', 'arquivado'])],
            'visibility' => [Rule::in(['publico', 'departamento', 'privado'])],
            'expiryDate' => ['nullable', 'date'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
