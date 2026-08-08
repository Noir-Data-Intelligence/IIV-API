<?php

namespace App\Http\Requests\Noticia;

use Illuminate\Foundation\Http\FormRequest;

class NoticiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'resumo' => ['nullable', 'string'],
            'conteudo' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'image_path' => ['nullable', 'string'],
            'destaque' => ['boolean'],
            'published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
