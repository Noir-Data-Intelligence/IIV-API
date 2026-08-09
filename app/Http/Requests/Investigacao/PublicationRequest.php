<?php

namespace App\Http\Requests\Investigacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'projectId' => ['nullable', 'uuid', 'exists:research_projects,id'],
            'type' => ['sometimes', Rule::in(['artigo', 'comunicacao', 'livro', 'tese', 'relatorio'])],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'venue' => ['nullable', 'string', 'max:255'],
            'doi' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'createdBy' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
